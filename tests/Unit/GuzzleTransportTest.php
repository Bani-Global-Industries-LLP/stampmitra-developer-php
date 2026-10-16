<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use StampMitra\Exception\NotFoundError;
use StampMitra\StampMitra;

final class GuzzleTransportTest extends TestCase
{
    public function testGuzzleClientGetsPerRequestTimeoutAndNoHttpExceptions(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(404, [], '{"error":{"code":"NOT_FOUND","message":"nope","requestId":"r"}}')]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $client = new StampMitra(apiKey: 'sk_test_abcdefgh12345678', httpClient: new Client(['handler' => $stack]));

        try {
            $client->orders->get('ord_1', ['timeout' => 5]);
            self::fail('expected exception');
        } catch (NotFoundError $e) {
            self::assertSame('r', $e->getRequestId());
        }
        self::assertSame(5.0, $history[0]['options']['timeout']);
    }

    public function testDefaultClientIsDiscovered(): void
    {
        self::assertSame('test', (new StampMitra(apiKey: 'sk_test_abcdefgh12345678'))->environment);
    }
}
