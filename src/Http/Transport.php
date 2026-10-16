<?php

declare(strict_types=1);

namespace StampMitra\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use StampMitra\Exception\AuthenticationError;
use StampMitra\Exception\ConflictError;
use StampMitra\Exception\ConnectionError;
use StampMitra\Exception\NotFoundError;
use StampMitra\Exception\PaymentRequiredError;
use StampMitra\Exception\PermissionError;
use StampMitra\Exception\RateLimitError;
use StampMitra\Exception\StampMitraError;
use StampMitra\Exception\TimeoutError;
use StampMitra\Exception\UpstreamError;
use StampMitra\Exception\ValidationError;
use StampMitra\Support\Redactor;
use StampMitra\Support\Uuid;

/**
 * Builds requests, sends them through the PSR-18 client, retries safely and
 * maps failures to typed exceptions.
 *
 * Retry rules:
 *  - GET is always retry-safe.
 *  - POST is retried only when an Idempotency-Key is present. The SDK adds a
 *    key to every POST the API documents it for (verification, duty, parties,
 *    orders, payment intents), so those are retry-safe; other POSTs (webhook
 *    replay) are never retried unless the caller supplies a key.
 *  - Retried: 429, 502, 503, 504, connection errors, timeouts.
 *  - The same Idempotency-Key is re-used for every attempt.
 */
final class Transport
{
    private const RETRY_STATUSES = [429, 502, 503, 504];
    private const BACKOFF_BASE_SECONDS = 0.5;
    private const BACKOFF_CAP_SECONDS = 8.0;
    private const RETRY_AFTER_CAP_SECONDS = 60.0;

    /** @var \Closure(float): void */
    private \Closure $sleeper;

    /**
     * @param array<string, string> $defaultHeaders
     * @param (callable(float): void)|null $sleeper Receives the delay in seconds; defaults to usleep
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly float $timeout,
        private readonly int $maxRetries,
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly array $defaultHeaders,
        private readonly string $userAgent,
        private readonly ?LoggerInterface $logger = null,
        ?callable $sleeper = null,
    ) {
        $this->sleeper = $sleeper !== null
            ? \Closure::fromCallable($sleeper)
            : static function (float $seconds): void {
                if ($seconds > 0) {
                    usleep((int) round($seconds * 1_000_000));
                }
            };
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, scalar|null> $query
     * @param bool $autoIdempotency Generate an Idempotency-Key when the caller did not pass one
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        array $query = [],
        ?RequestOptions $options = null,
        bool $autoIdempotency = false,
    ): ApiResponse {
        $options ??= new RequestOptions();
        $idempotencyKey = $options->idempotencyKey;
        if ($idempotencyKey === null && $autoIdempotency) {
            $idempotencyKey = Uuid::v4();
        }
        $isGet = $method === 'GET';
        $retrySafe = $isGet || $idempotencyKey !== null;
        $maxRetries = max(0, $options->maxRetries ?? $this->maxRetries);
        $timeout = $options->timeout ?? $this->timeout;

        $attempt = 0;
        while (true) {
            ++$attempt;
            $request = $this->buildRequest($method, $path, $body, $query, $options, $idempotencyKey);
            $this->logRequest($request, $attempt);

            try {
                $response = $this->send($request, $timeout);
            } catch (ClientExceptionInterface $e) {
                $error = $this->connectionError($e, $timeout);
                $this->logger?->debug('stampmitra transport error', ['attempt' => $attempt, 'error' => $error->getMessage()]);
                if ($retrySafe && $attempt <= $maxRetries) {
                    $this->pause($this->backoff($attempt));
                    continue;
                }
                throw $error;
            }

            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
            /** @var array<string, list<string>> $headers */
            $text = (string) $response->getBody();
            $this->logResponse($status, $text, $attempt);

            if ($status >= 200 && $status < 300) {
                return new ApiResponse(
                    $status,
                    $headers,
                    $this->decodeSuccess($text, $status, $headers),
                    $this->headerValue($headers, 'x-request-id'),
                    $attempt,
                );
            }

            $error = $this->mapError($status, $text, $headers);
            if ($retrySafe && $attempt <= $maxRetries && in_array($status, self::RETRY_STATUSES, true)) {
                $retryAfter = $error instanceof RateLimitError ? $error->getRetryAfter() : $this->retryAfter($headers);
                $this->pause($retryAfter !== null ? min($retryAfter, self::RETRY_AFTER_CAP_SECONDS) : $this->backoff($attempt));
                continue;
            }

            throw $error;
        }
    }

    public function backoff(int $attempt): float
    {
        $ceiling = min(self::BACKOFF_CAP_SECONDS, self::BACKOFF_BASE_SECONDS * (2 ** ($attempt - 1)));

        return random_int(0, 1_000_000) / 1_000_000 * $ceiling; // full jitter
    }

    private function pause(float $seconds): void
    {
        ($this->sleeper)($seconds);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, scalar|null> $query
     */
    private function buildRequest(
        string $method,
        string $path,
        ?array $body,
        array $query,
        RequestOptions $options,
        ?string $idempotencyKey,
    ): RequestInterface {
        $url = rtrim($this->baseUrl, '/') . $path;
        $params = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            $params[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }
        if ($params !== []) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('User-Agent', $this->userAgent);

        foreach ($this->defaultHeaders as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        foreach ($options->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        if ($idempotencyKey !== null) {
            $request = $request->withHeader('Idempotency-Key', $idempotencyKey);
        }
        if ($method !== 'GET') {
            $json = json_encode($body === null || $body === [] ? new \stdClass() : $body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $request = $request->withBody($this->streamFactory->createStream($json));
        }

        return $request;
    }

    private function send(RequestInterface $request, float $timeout): ResponseInterface
    {
        if ($this->http instanceof \GuzzleHttp\ClientInterface) {
            return $this->http->send($request, [
                'timeout' => $timeout,
                'connect_timeout' => min(10.0, $timeout),
                'http_errors' => false,
                'allow_redirects' => false,
            ]);
        }

        // Plain PSR-18 offers no per-request timeout: configure it on the injected client.
        return $this->http->sendRequest($request);
    }

    private function connectionError(ClientExceptionInterface $e, float $timeout): ConnectionError
    {
        $message = $e->getMessage();
        $timedOut = preg_match('/timed out|timeout|cURL error 28/i', $message) === 1;
        if ($timedOut) {
            return new TimeoutError("Request timed out after {$timeout}s: $message", null, 'TIMEOUT', null, null, null, [], $e);
        }
        $label = $e instanceof NetworkExceptionInterface ? 'Network error' : 'Request failed';

        return new ConnectionError("$label: $message", null, 'CONNECTION_ERROR', null, null, null, [], $e);
    }

    /**
     * @param array<string, list<string>> $headers
     * @return array<string, mixed>
     */
    private function decodeSuccess(string $text, int $status, array $headers): array
    {
        if (trim($text) === '') {
            return [];
        }
        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new StampMitraError('The API returned a success status with a non-JSON body', $status, 'INVALID_RESPONSE', $this->headerValue($headers, 'x-request-id'), $text, null, $headers, $e);
        }
        if (!is_array($decoded)) {
            return ['value' => $decoded];
        }
        if (array_is_list($decoded)) {
            return ['items' => $decoded];
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function mapError(int $status, string $text, array $headers): StampMitraError
    {
        $decoded = json_decode($text, true);
        $body = $text === '' ? null : $text;
        if (is_array($decoded)) {
            $body = [];
            foreach ($decoded as $k => $v) {
                $body[(string) $k] = $v;
            }
        }
        $err = is_array($decoded) && isset($decoded['error']) && is_array($decoded['error']) ? $decoded['error'] : [];
        $message = isset($err['message']) && is_string($err['message']) ? $err['message'] : "HTTP $status";
        $code = isset($err['code']) && is_string($err['code']) ? $err['code'] : null;
        $field = isset($err['field']) && is_string($err['field']) ? $err['field'] : null;
        $requestId = isset($err['requestId']) && is_string($err['requestId']) ? $err['requestId'] : $this->headerValue($headers, 'x-request-id');
        return match (true) {
            $status === 400 => new ValidationError($message, $status, $code, $requestId, $body, $field, $headers),
            $status === 401 => new AuthenticationError($message, $status, $code, $requestId, $body, $field, $headers),
            $status === 402 => new PaymentRequiredError($message, $status, $code, $requestId, $body, $field, $headers),
            $status === 403 => new PermissionError($message, $status, $code, $requestId, $body, $field, $headers),
            $status === 404 => new NotFoundError($message, $status, $code, $requestId, $body, $field, $headers),
            $status === 409 => new ConflictError($message, $status, $code, $requestId, $body, $field, $headers),
            $status === 429 => new RateLimitError($message, $status, $code, $requestId, $body, $field, $headers, $this->retryAfter($headers)),
            $status >= 500 => new UpstreamError($message, $status, $code, $requestId, $body, $field, $headers),
            default => new StampMitraError($message, $status, $code, $requestId, $body, $field, $headers),
        };
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function retryAfter(array $headers): ?float
    {
        $value = $this->headerValue($headers, 'retry-after');
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return max(0.0, (float) $value);
        }
        $time = strtotime($value);

        return $time === false ? null : max(0.0, (float) ($time - time()));
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $values) {
            if (strcasecmp((string) $key, $name) === 0) {
                return $values[0] ?? null;
            }
        }

        return null;
    }

    private function logRequest(RequestInterface $request, int $attempt): void
    {
        if ($this->logger === null) {
            return;
        }
        $this->logger->debug('stampmitra request', [
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'attempt' => $attempt,
            'headers' => Redactor::headers(array_map(static fn (array $v): array => array_values($v), $request->getHeaders())),
            'body' => Redactor::body((string) $request->getBody()),
        ]);
    }

    private function logResponse(int $status, string $text, int $attempt): void
    {
        $this->logger?->debug('stampmitra response', [
            'status' => $status,
            'attempt' => $attempt,
            'body' => Redactor::body($text),
        ]);
    }
}
