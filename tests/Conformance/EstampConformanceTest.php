<?php

declare(strict_types=1);

namespace StampMitra\Tests\Conformance;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StampMitra\Exception\ConflictError;
use StampMitra\Exception\RateLimitError;
use StampMitra\Exception\StampMitraError;
use StampMitra\Exception\UpstreamError;
use StampMitra\StampMitra;
use StampMitra\Tests\Support\FixtureOperations;
use StampMitra\Tests\Support\ReplaysFixtures;

/**
 * Contract 1.2.0 operations (e-Stamp catalogue, orders, stamps, wallets, account) against the spec
 * repo's mock server: every golden fixture replayed through the SDK, plus multipart upload,
 * Idempotency-Key handling, Retry-After and the never-retry set.
 *
 *   MOCK_SERVER_URL=http://127.0.0.1:4010 composer test:conformance
 */
final class EstampConformanceTest extends TestCase
{
    use ReplaysFixtures;

    private const KEY = 'sk_test_abcdefgh12345678';
    private string $url = '';
    /** @var list<float> */
    private array $sleeps = [];

    protected function setUp(): void
    {
        $url = getenv('MOCK_SERVER_URL');
        if ($url === false || $url === '') {
            if (getenv('CONFORMANCE_REQUIRED') === '1') {
                self::fail('MOCK_SERVER_URL is required when CONFORMANCE_REQUIRED=1');
            }
            self::markTestSkipped('MOCK_SERVER_URL not set');
        }
        $this->url = rtrim($url, '/');
        $this->sleeps = [];
        file_get_contents($this->url . '/__mock/reset', false, stream_context_create(['http' => ['method' => 'POST', 'content' => '', 'header' => "Content-Length: 0\r\n"]]));
    }

    /** @return list<array{method: string, path: string, query: array<string, string>, headers: array<string, string>, body: mixed}> */
    private function recorded(): array
    {
        /** @var list<array{method: string, path: string, query: array<string, string>, headers: array<string, string>, body: mixed}> $r */
        $r = json_decode((string) file_get_contents($this->url . '/__mock/requests'), true, 512, JSON_THROW_ON_ERROR);

        return $r;
    }

    private function client(int $maxRetries = 0): StampMitra
    {
        return new StampMitra(self::KEY, $this->url, maxRetries: $maxRetries, sleeper: function (float $s): void {
            $this->sleeps[] = $s;
        });
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function fixtures(): array
    {
        $out = [];
        foreach (FixtureOperations::fixtures() as $fx) {
            $out[(string) $fx['name']] = [$fx];
        }

        return $out;
    }

    /** @param array<string, mixed> $fx */
    #[DataProvider('fixtures')]
    public function testFixtureReplayAgainstMock(array $fx): void
    {
        /** @var array{method: string, path: string, query?: array<string, string>, body?: array<string, mixed>, multipart?: array{parts: list<array<string, string>>}} $req */
        $req = $fx['request'];
        /** @var array{status: int} $res */
        $res = $fx['response'];
        [$key, $params] = FixtureOperations::route($req['method'], $req['path']) ?? self::fail('no route');
        $call = FixtureOperations::calls()[$key];

        $result = null;
        $error = null;
        try {
            $result = $call($this->client(), $params, $req, ['headers' => ['x-mock-scenario' => (string) $fx['scenario']]]);
        } catch (StampMitraError $e) {
            $error = $e;
        }
        if ($res['status'] >= 400) {
            self::assertNotNull($error, (string) $fx['name']);
            $this->assertErrorMatches($fx, $error);
        } else {
            self::assertNull($error, $error?->getMessage() ?? '');
            $this->assertSuccessMatches($fx, $result);
        }

        $seen = $this->recorded();
        self::assertCount(1, $seen);
        self::assertSame($req['method'], $seen[0]['method']);
        self::assertSame($req['path'], $seen[0]['path']);
        if ($req['method'] === 'GET') {
            self::assertEquals($req['query'] ?? [], $seen[0]['query']);
        }
        if (isset($req['body']) && $req['method'] === 'POST') {
            self::assertEquals($req['body'], $seen[0]['body']);
        }
        if (isset($req['multipart'])) {
            $parts = $req['multipart']['parts'];
            /** @var array{multipart: list<array{name: string, filename?: string, contentType?: string, size: int, value?: string}>} $body */
            $body = $seen[0]['body'];
            self::assertCount(count($parts), $body['multipart']);
            foreach ($parts as $i => $part) {
                self::assertSame($part['name'], $body['multipart'][$i]['name']);
                if (isset($part['filename'])) {
                    self::assertSame($part['filename'], $body['multipart'][$i]['filename'] ?? null);
                    self::assertSame($part['contentType'], $body['multipart'][$i]['contentType'] ?? null);
                    self::assertSame(strlen($part['content']), $body['multipart'][$i]['size']);
                } else {
                    self::assertSame($part['value'], $body['multipart'][$i]['value'] ?? null);
                }
            }
            self::assertStringStartsWith('multipart/form-data; boundary=', $seen[0]['headers']['content-type']);
        }
    }

    public function testOrderCreationGeneratesAnIdempotencyKeyAndReusesItAcrossRetries(): void
    {
        $fx = FixtureOperations::successFixture('POST /v1/estamp/orders');
        /** @var array<string, mixed> $body */
        $body = $fx['request']['body'];
        $this->client(2)->estamp->orders->create($body, ['headers' => ['x-mock-scenario' => 'seq:auto1:503,502,201']]);
        $seen = $this->recorded();
        self::assertCount(3, $seen);
        $keys = array_unique(array_map(static fn (array $r): string => $r['headers']['idempotency-key'], $seen));
        self::assertCount(1, $keys);
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $keys[0]);

        $this->client()->estamp->orders->create($body, ['idempotencyKey' => 'mine-1']);
        $seen = $this->recorded();
        self::assertSame('mine-1', $seen[3]['headers']['idempotency-key']);
    }

    public function testUploadSendsMultipartAndGeneratesAKeyForAFilePath(): void
    {
        $path = sys_get_temp_dir() . '/pan-' . bin2hex(random_bytes(4)) . '.png';
        file_put_contents($path, "\x89PNG\r\n\x1a\n" . str_repeat('x', 100));
        try {
            $doc = $this->client()->estamp->orders->uploadDocument('estord_9c1f2a3b', $path);
        } finally {
            unlink($path);
        }
        self::assertSame('estdoc_8d41c2', $doc->documentId);
        $seen = $this->recorded();
        self::assertCount(1, $seen);
        self::assertNotEmpty($seen[0]['headers']['idempotency-key']);
        /** @var array{multipart: list<array<string, mixed>>} $body */
        $body = $seen[0]['body'];
        self::assertSame('file', $body['multipart'][0]['name']);
        self::assertSame(basename($path), $body['multipart'][0]['filename']);
        self::assertSame('image/png', $body['multipart'][0]['contentType']);
        self::assertSame(108, $body['multipart'][0]['size']);
        self::assertSame(['name' => 'type', 'size' => 8, 'value' => 'pan_card'], $body['multipart'][1]);
    }

    public function testUploadIsResentWholeWithTheSameKeyOnRetry(): void
    {
        $this->client(2)->estamp->orders->uploadDocument('estord_1', ['contents' => "%PDF-1.4\nx", 'filename' => 'pan.pdf'], 'pan_card', ['headers' => ['x-mock-scenario' => 'seq:up:502,201']]);
        $seen = $this->recorded();
        self::assertCount(2, $seen);
        self::assertSame($seen[0]['headers']['idempotency-key'], $seen[1]['headers']['idempotency-key']);
        self::assertSame($seen[0]['body'], $seen[1]['body']);
    }

    public function testRetryAfterIsHonouredOn429And503And409InProgress(): void
    {
        $this->client(1)->stampDutyWallet->balance(['headers' => ['x-mock-scenario' => 'seq:w:429,200']]);
        $this->client(1)->account->balance(['headers' => ['x-mock-scenario' => 'seq:b:503,200']]);
        $fx = FixtureOperations::successFixture('POST /v1/estamp/orders');
        /** @var array<string, mixed> $body */
        $body = $fx['request']['body'];
        $this->client(1)->estamp->orders->create($body, ['headers' => ['x-mock-scenario' => 'seq:c:409,201']]);
        self::assertSame([1.0, 1.0, 1.0], $this->sleeps);

        $this->sleeps = [];
        try {
            $this->client(1)->estamp->getWallet(['headers' => ['x-mock-scenario' => 'retry-after:7']]);
            self::fail('expected RateLimitError');
        } catch (RateLimitError $e) {
            self::assertSame(7.0, $e->getRetryAfter());
            self::assertSame([7.0], $this->sleeps);
        }
    }

    public function testStateConflict409IsNeverRetried(): void
    {
        try {
            $this->client(2)->estamp->orders->cancel('estord_1', ['headers' => ['x-mock-scenario' => 'seq:k:409,200']]);
            self::fail('expected ConflictError');
        } catch (ConflictError) {
            self::assertCount(1, $this->recorded());
        }
    }

    /** @return array<string, array{callable(StampMitra, array<string, mixed>): mixed}> */
    public static function neverRetried(): array
    {
        return [
            'order cancel' => [fn (StampMitra $c, array $o) => $c->estamp->orders->cancel('estord_1', $o)],
            'stamp cancel' => [fn (StampMitra $c, array $o) => $c->estamp->stamps->cancel('eststamp_1', $o)],
            'stamp replace' => [fn (StampMitra $c, array $o) => $c->estamp->stamps->replace('eststamp_1', $o)],
            'pan-card link' => [fn (StampMitra $c, array $o) => $c->estamp->stamps->setPanCard('eststamp_1', 'https://example.com/pan.jpg', $o)],
            'stamp duty top-up' => [fn (StampMitra $c, array $o) => $c->stampDutyWallet->topup(1000, $o)],
            'service fee top-up' => [fn (StampMitra $c, array $o) => $c->serviceFeeWallet->topup(1000, $o)],
            'webhook replay' => [fn (StampMitra $c, array $o) => $c->webhooks->events->replay('evt_1', $o)],
        ];
    }

    /** @param callable(StampMitra, array<string, mixed>): mixed $call */
    #[DataProvider('neverRetried')]
    public function testNeverAutoRetriedEvenOn503AndEvenWithACallerKey(callable $call): void
    {
        try {
            $call($this->client(2), ['idempotencyKey' => 'caller-key', 'headers' => ['x-mock-scenario' => 'seq:nr:503,200']]);
            self::fail('expected UpstreamError');
        } catch (UpstreamError) {
            $seen = $this->recorded();
            self::assertCount(1, $seen);
            self::assertArrayNotHasKey('idempotency-key', $seen[0]['headers']);
            self::assertSame([], $this->sleeps);
        }
    }

    public function testValidateIsRetrySafeAndSendsNoKey(): void
    {
        $fx = FixtureOperations::successFixture('POST /v1/estamp/orders/validate');
        /** @var array<string, mixed> $body */
        $body = $fx['request']['body'];
        $result = $this->client(2)->estamp->orders->validate($body, ['headers' => ['x-mock-scenario' => 'seq:v:503,200']]);
        self::assertTrue($result->valid);
        $seen = $this->recorded();
        self::assertCount(2, $seen);
        self::assertArrayNotHasKey('idempotency-key', $seen[0]['headers']);
    }
}
