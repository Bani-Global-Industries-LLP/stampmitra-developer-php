<?php

declare(strict_types=1);

namespace StampMitra\Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use StampMitra\StampMitra;

/** Scripted PSR-18 client that records every request. */
final class FakeClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var list<float> */
    public array $sleeps = [];

    /** @var list<ResponseInterface|\Throwable> */
    private array $queue;

    /** @param list<ResponseInterface|\Throwable> $queue */
    public function __construct(array $queue = [])
    {
        $this->queue = $queue;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $next = array_shift($this->queue);
        if ($next === null) {
            throw new \LogicException('FakeClient queue exhausted');
        }
        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }

    /**
     * @param array<array-key, mixed> $body
     * @param array<string, string> $headers
     */
    public static function json(int $status, array $body, array $headers = []): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'] + $headers, (string) json_encode($body));
    }

    /** @param array<string, mixed> $options */
    public function client(array $options = []): StampMitra
    {
        return new StampMitra(
            ...($options + [
                'apiKey' => 'sk_test_abcdefgh12345678',
                'httpClient' => $this,
                'sleeper' => function (float $s): void {
                    $this->sleeps[] = $s;
                },
            ]),
        );
    }

    public function lastBody(): string
    {
        $last = $this->requests[array_key_last($this->requests)];

        return (string) $last->getBody();
    }
}
