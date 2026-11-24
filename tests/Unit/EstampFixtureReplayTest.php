<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StampMitra\Exception\StampMitraError;
use StampMitra\Tests\Support\FakeClient;
use StampMitra\Tests\Support\FixtureOperations;
use StampMitra\Tests\Support\ReplaysFixtures;

/**
 * Replays the 195 golden fixtures of the 31 contract-1.2.0 operations through the SDK with a
 * scripted PSR-18 client (no network): request line, query, body and multipart parts out;
 * models, error classes and the typed error accessors back.
 */
final class EstampFixtureReplayTest extends TestCase
{
    use ReplaysFixtures;

    /** @return array<string, array{array<string, mixed>}> */
    public static function fixtures(): array
    {
        $out = [];
        foreach (FixtureOperations::fixtures() as $fx) {
            $out[(string) $fx['name']] = [$fx];
        }

        return $out;
    }

    public function testEveryNewOperationIsCovered(): void
    {
        self::assertCount(31, FixtureOperations::calls());
        self::assertCount(195, FixtureOperations::fixtures());
    }

    /** @param array<string, mixed> $fx */
    #[DataProvider('fixtures')]
    public function testFixtureRoundTrip(array $fx): void
    {
        /** @var array{status: int, headers?: array<string, string>, body: array<array-key, mixed>} $res */
        $res = $fx['response'];
        /** @var array{method: string, path: string, query?: array<string, string>, body?: array<string, mixed>, multipart?: array<string, mixed>} $req */
        $req = $fx['request'];
        $headers = $res['headers'] ?? [];
        unset($headers['Content-Type']);
        $fake = new FakeClient([FakeClient::json($res['status'], $res['body'], $headers)]);
        $client = $fake->client(['maxRetries' => 0]);

        $route = FixtureOperations::route($req['method'], $req['path']);
        self::assertNotNull($route, $req['path']);
        [$key, $params] = $route;
        $call = FixtureOperations::calls()[$key];

        $result = null;
        $error = null;
        try {
            $result = $call($client, $params, $req, []);
        } catch (StampMitraError $e) {
            $error = $e;
        }

        if ($res['status'] >= 400) {
            self::assertNotNull($error, 'expected an error for ' . $fx['name']);
            $this->assertErrorMatches($fx, $error);
        } else {
            self::assertNull($error, $error?->getMessage() ?? '');
            $this->assertSuccessMatches($fx, $result);
        }

        // what went out
        self::assertCount(1, $fake->requests);
        $sent = $fake->requests[0];
        self::assertSame($req['method'], $sent->getMethod());
        self::assertSame($req['path'], $sent->getUri()->getPath());
        parse_str($sent->getUri()->getQuery(), $sentQuery);
        if ($req['method'] === 'GET') {
            self::assertEquals($req['query'] ?? [], $sentQuery);
        }
        self::assertSame('Bearer sk_test_abcdefgh12345678', $sent->getHeaderLine('Authorization'));
        if (isset($req['body']) && $req['method'] === 'POST') {
            self::assertEquals($req['body'], json_decode((string) $sent->getBody(), true));
        }
        if (isset($req['multipart'])) {
            /** @var array{parts: list<array{name: string, filename?: string, contentType?: string, content?: string, value?: string}>} $mp */
            $mp = $req['multipart'];
            $sentParts = FixtureOperations::parseMultipart($sent->getHeaderLine('Content-Type'), (string) $sent->getBody());
            self::assertCount(count($mp['parts']), $sentParts);
            foreach ($mp['parts'] as $i => $part) {
                self::assertSame($part['name'], $sentParts[$i]['name']);
                if (isset($part['filename'])) {
                    self::assertSame($part['filename'], $sentParts[$i]['filename'] ?? null);
                    self::assertSame($part['contentType'] ?? null, $sentParts[$i]['contentType'] ?? null);
                    self::assertSame(strlen((string) ($part['content'] ?? '')), $sentParts[$i]['size']);
                } else {
                    self::assertSame($part['value'] ?? null, $sentParts[$i]['value'] ?? null);
                }
            }
        }
    }
}
