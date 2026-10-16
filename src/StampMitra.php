<?php

declare(strict_types=1);

namespace StampMitra;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use StampMitra\Exception\AuthenticationError;
use StampMitra\Exception\ValidationError;
use StampMitra\Http\Transport;
use StampMitra\Resource\Estamp;
use StampMitra\Resource\Logs;
use StampMitra\Resource\Orders;
use StampMitra\Resource\Payments;
use StampMitra\Resource\Services;
use StampMitra\Resource\Usage;
use StampMitra\Resource\Verify;
use StampMitra\Resource\Webhooks;

/**
 * StampMitra Developer API client.
 *
 *     $client = new StampMitra(apiKey: getenv('STAMPMITRA_API_KEY') ?: null);
 *     $result = $client->verify->pan('ABCDE1234F');
 *
 * Server-side only: never ship sk_ keys in mobile apps or browsers.
 */
final class StampMitra
{
    public const VERSION = '0.1.0';
    public const DEFAULT_BASE_URL = 'https://api-b2b.stampmitra.in';
    private const KEY_PATTERN = '/^sk_(test|live)_[A-Za-z0-9_-]{8,}$/';

    public readonly Verify $verify;
    public readonly Services $services;
    public readonly Estamp $estamp;
    public readonly Orders $orders;
    public readonly Payments $payments;
    public readonly Usage $usage;
    public readonly Logs $logs;
    public readonly Webhooks $webhooks;

    /** 'test' or 'live' (or 'custom' when allowCustomKey is used with a non sk_ key). */
    public readonly string $environment;

    /**
     * @param string|null           $apiKey         Defaults to the STAMPMITRA_API_KEY environment variable
     * @param float                 $timeout        Seconds (per attempt). Applied per request with Guzzle; with another PSR-18 client configure it on that client
     * @param int                   $maxRetries     Automatic retries for safe requests (default 2)
     * @param array<string, string> $defaultHeaders Sent with every request
     * @param array{name?: string, version?: string, url?: string}|null $appInfo Appended to the User-Agent
     * @param (callable(float): void)|null $sleeper Injected sleep (seconds), mainly for tests
     */
    public function __construct(
        #[\SensitiveParameter]
        ?string $apiKey = null,
        string $baseUrl = self::DEFAULT_BASE_URL,
        float $timeout = 30.0,
        int $maxRetries = 2,
        ?ClientInterface $httpClient = null,
        array $defaultHeaders = [],
        ?array $appInfo = null,
        ?LoggerInterface $logger = null,
        bool $validateInput = true,
        bool $allowCustomKey = false,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?callable $sleeper = null,
    ) {
        self::assertSecureBaseUrl($baseUrl);
        $envKey = getenv('STAMPMITRA_API_KEY');
        $apiKey ??= ($envKey !== false && $envKey !== '') ? $envKey : null;
        if ($apiKey === null || $apiKey === '') {
            throw new AuthenticationError('No API key provided. Pass apiKey or set STAMPMITRA_API_KEY.');
        }
        $apiKey = trim($apiKey);
        if (preg_match(self::KEY_PATTERN, $apiKey, $m) === 1) {
            $this->environment = $m[1];
        } elseif ($allowCustomKey) {
            $this->environment = 'custom';
        } else {
            throw new AuthenticationError('API key must look like sk_test_... or sk_live_... (pass allowCustomKey: true to override).');
        }

        if ($httpClient === null) {
            $httpClient = self::defaultHttpClient($timeout);
        }
        $requestFactory ??= Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory ??= Psr17FactoryDiscovery::findStreamFactory();

        $userAgent = 'stampmitra-php/' . self::VERSION . ' (PHP ' . PHP_VERSION . ')';
        if ($appInfo !== null && isset($appInfo['name'])) {
            $userAgent .= ' ' . $appInfo['name'];
            if (isset($appInfo['version'])) {
                $userAgent .= '/' . $appInfo['version'];
            }
            if (isset($appInfo['url'])) {
                $userAgent .= ' (' . $appInfo['url'] . ')';
            }
        }

        $transport = new Transport(
            $apiKey,
            $baseUrl,
            $timeout,
            $maxRetries,
            $httpClient,
            $requestFactory,
            $streamFactory,
            $defaultHeaders,
            $userAgent,
            $logger,
            $sleeper,
        );

        $this->verify = new Verify($transport, $validateInput);
        $this->services = new Services($transport, $validateInput);
        $this->estamp = new Estamp($transport, $validateInput);
        $this->orders = new Orders($transport, $validateInput);
        $this->payments = new Payments($transport, $validateInput);
        $this->usage = new Usage($transport, $validateInput);
        $this->logs = new Logs($transport, $validateInput);
        $this->webhooks = new Webhooks($transport, $validateInput);
    }

    /** The API key travels in the Authorization header, so plain http is only allowed for loopback hosts (mock servers, tests). */
    private static function assertSecureBaseUrl(string $baseUrl): void
    {
        $parts = parse_url($baseUrl);
        $scheme = is_array($parts) ? strtolower($parts['scheme'] ?? '') : '';
        $host = is_array($parts) ? strtolower(trim($parts['host'] ?? '', '[]')) : '';
        if ($host === '' || !in_array($scheme, ['http', 'https'], true)) {
            throw ValidationError::forField('baseUrl', 'baseUrl must be an absolute https URL');
        }
        if ($scheme === 'http' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw ValidationError::forField('baseUrl', 'baseUrl must use https (http is only allowed for localhost)');
        }
    }

    private static function defaultHttpClient(float $timeout): ClientInterface
    {
        if (class_exists(\GuzzleHttp\Client::class)) {
            return new \GuzzleHttp\Client(['timeout' => $timeout, 'http_errors' => false]);
        }

        return Psr18ClientDiscovery::find();
    }
}
