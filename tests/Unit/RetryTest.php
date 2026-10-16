<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use StampMitra\Exception\ConnectionError;
use StampMitra\Exception\RateLimitError;
use StampMitra\Exception\TimeoutError;
use StampMitra\Exception\UpstreamError;
use StampMitra\Tests\Support\FakeClient;
use StampMitra\Tests\Support\FakeNetworkError;

final class RetryTest extends TestCase
{
    private function err(int $status, string $code = 'X'): \GuzzleHttp\Psr7\Response
    {
        return FakeClient::json($status, ['error' => ['code' => $code, 'message' => 'm', 'requestId' => 'r']]);
    }

    public function testGetRetriesOn503ThenSucceedsWithJitteredBackoff(): void
    {
        $fake = new FakeClient([$this->err(503), $this->err(502), FakeClient::json(200, ['totalApiRequests' => 1])]);
        $res = $fake->client()->usage->summary();

        self::assertSame(1, $res->totalApiRequests);
        self::assertCount(3, $fake->requests);
        self::assertCount(2, $fake->sleeps);
        self::assertLessThanOrEqual(0.5, $fake->sleeps[0]);
        self::assertLessThanOrEqual(1.0, $fake->sleeps[1]);
        self::assertSame(3, $res->response()?->attempts);
    }

    public function testRetryAfterHeaderIsHonoured(): void
    {
        $fake = new FakeClient([
            FakeClient::json(429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'm', 'requestId' => 'r']], ['Retry-After' => '3']),
            FakeClient::json(200, []),
        ]);
        $fake->client()->usage->summary();
        self::assertSame([3.0], $fake->sleeps);
    }

    public function testRetriesExhaustedRaisesLastError(): void
    {
        $fake = new FakeClient([$this->err(429), $this->err(429), $this->err(429)]);
        try {
            $fake->client(['maxRetries' => 2])->usage->summary();
            self::fail('expected exception');
        } catch (RateLimitError) {
            self::assertCount(3, $fake->requests);
        }
    }

    public function testNonRetryableStatusIsNotRetried(): void
    {
        $fake = new FakeClient([$this->err(500)]);
        $this->expectException(UpstreamError::class);
        try {
            $fake->client()->usage->summary();
        } finally {
            self::assertCount(1, $fake->requests);
        }
    }

    public function testPerRequestMaxRetriesOverride(): void
    {
        $fake = new FakeClient([$this->err(503)]);
        try {
            $fake->client()->usage->summary(['maxRetries' => 0]);
            self::fail('expected exception');
        } catch (UpstreamError) {
            self::assertCount(1, $fake->requests);
        }
    }

    public function testOrderCreateAutoGeneratesAndReusesIdempotencyKeyAcrossRetries(): void
    {
        $fake = new FakeClient([$this->err(503), $this->err(502), FakeClient::json(201, ['externalOrderId' => 'ord_1'])]);
        $order = $fake->client()->orders->create([
            'externalOrderId' => 'e1', 'externalCustomerId' => 'c1', 'serviceCode' => 'estamp.instrument.rent_agreement',
        ]);

        self::assertSame('ord_1', $order->get('externalOrderId'));
        self::assertCount(3, $fake->requests);
        $keys = array_map(static fn ($r) => $r->getHeaderLine('Idempotency-Key'), $fake->requests);
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $keys[0]);
        self::assertSame([$keys[0], $keys[0], $keys[0]], $keys);
    }

    public function testCallerSuppliedIdempotencyKeyIsUsed(): void
    {
        $fake = new FakeClient([FakeClient::json(200, ['id' => 'pi_1'])]);
        $fake->client()->payments->intents->create(['amount' => 100], ['idempotencyKey' => 'my-key-1']);
        self::assertSame('my-key-1', $fake->requests[0]->getHeaderLine('Idempotency-Key'));
    }

    public function testEachIdempotentOperationGetsAKey(): void
    {
        $fake = new FakeClient([FakeClient::json(200, []), FakeClient::json(200, [])]);
        $client = $fake->client();
        $client->payments->intents->create(['amount' => 1]);
        $client->estamp->addParties('ord_1', ['parties' => []]);
        self::assertNotSame('', $fake->requests[0]->getHeaderLine('Idempotency-Key'));
        self::assertNotSame('', $fake->requests[1]->getHeaderLine('Idempotency-Key'));
    }

    public function testVerificationRetriesReuseTheSameIdempotencyKey(): void
    {
        $fake = new FakeClient([$this->err(503), FakeClient::json(200, ['valid' => true, 'referenceId' => 'R'])]);
        $res = $fake->client()->verify->pan('ABCDE1234F');

        self::assertTrue($res->valid);
        self::assertCount(2, $fake->requests);
        $key = $fake->requests[0]->getHeaderLine('Idempotency-Key');
        self::assertNotSame('', $key);
        self::assertSame($key, $fake->requests[1]->getHeaderLine('Idempotency-Key'));
    }

    public function testKeylessPostIsNeverRetried(): void
    {
        // Webhook replay carries no Idempotency-Key unless the caller supplies one.
        foreach ([$this->err(503), $this->err(429), new FakeNetworkError(new Request('POST', 'https://x.test'))] as $failure) {
            $fake = new FakeClient([$failure, FakeClient::json(200, [])]);
            try {
                $fake->client()->webhooks->events->replay('evt_1');
                self::fail('expected exception');
            } catch (UpstreamError | RateLimitError | ConnectionError) {
                self::assertCount(1, $fake->requests);
                self::assertSame([], $fake->sleeps);
            }
        }
    }

    public function testKeyedReplayIsRetried(): void
    {
        $fake = new FakeClient([$this->err(502), FakeClient::json(200, ['replayed' => true])]);
        $res = $fake->client()->webhooks->events->replay('evt_1', ['idempotencyKey' => 'k1']);
        self::assertTrue($res->get('replayed'));
        self::assertCount(2, $fake->requests);
    }

    public function testGetRetriesConnectionErrorsThenRaises(): void
    {
        $e = static fn () => new FakeNetworkError(new Request('GET', 'https://x.test'));
        $fake = new FakeClient([$e(), $e(), $e()]);
        $this->expectException(ConnectionError::class);
        try {
            $fake->client()->usage->summary();
        } finally {
            self::assertCount(3, $fake->requests);
        }
    }

    public function testTimeoutIsMappedToTimeoutError(): void
    {
        $fake = new FakeClient([new FakeNetworkError(new Request('GET', 'https://x.test'), 'cURL error 28: Operation timed out')]);
        $this->expectException(TimeoutError::class);
        $fake->client(['maxRetries' => 0])->usage->summary();
    }

    public function testBackoffNeverExceedsCap(): void
    {
        $t = new \ReflectionMethod(\StampMitra\Http\Transport::class, 'backoff');
        $transport = (new \ReflectionClass(\StampMitra\Http\Transport::class))->newInstanceWithoutConstructor();
        for ($attempt = 1; $attempt <= 12; ++$attempt) {
            self::assertLessThanOrEqual(8.0, $t->invoke($transport, $attempt));
        }
    }
}
