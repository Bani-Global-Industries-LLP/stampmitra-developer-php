<?php

declare(strict_types=1);

namespace StampMitra\Tests\Support;

use StampMitra\Exception\AuthenticationError;
use StampMitra\Exception\ConflictError;
use StampMitra\Exception\NotFoundError;
use StampMitra\Exception\PaymentRequiredError;
use StampMitra\Exception\PermissionError;
use StampMitra\Exception\RateLimitError;
use StampMitra\Exception\StampMitraError;
use StampMitra\Exception\UpstreamError;
use StampMitra\Exception\ValidationError;
use StampMitra\Model\Model;

/** Assertions shared by the fixture replays (FakeClient and mock server). Use inside a TestCase. */
trait ReplaysFixtures
{
    /** @param array<string, mixed> $fx */
    private function assertSuccessMatches(array $fx, mixed $result): void
    {
        /** @var array{status: int, body: mixed} $res */
        $res = $fx['response'];
        self::assertInstanceOf(Model::class, $result);
        self::assertSame($res['status'], $result->response()?->status);
        $expected = is_array($res['body']) && array_is_list($res['body']) ? ['items' => $res['body']] : $res['body'];
        self::assertEquals($expected, $result->raw(), (string) $fx['name']);
    }

    /** @param array<string, mixed> $fx */
    private function assertErrorMatches(array $fx, \Throwable $e): void
    {
        /** @var array{status: int, headers?: array<string, string>, body: array{error: array<string, mixed>}} $res */
        $res = $fx['response'];
        $classes = [
            400 => ValidationError::class, 401 => AuthenticationError::class, 402 => PaymentRequiredError::class,
            403 => PermissionError::class, 404 => NotFoundError::class, 409 => ConflictError::class,
            422 => ValidationError::class, 429 => RateLimitError::class, 502 => UpstreamError::class, 503 => UpstreamError::class,
        ];
        self::assertInstanceOf($classes[$res['status']], $e, (string) $fx['name']);
        self::assertInstanceOf(StampMitraError::class, $e);
        $err = $res['body']['error'];
        self::assertSame($res['status'], $e->getStatus());
        self::assertSame($err['code'], $e->getErrorCode());
        self::assertSame($err['message'], $e->getMessage());
        self::assertSame($err['requestId'], $e->getRequestId());
        $details = $err['details'] ?? [];
        self::assertSame($details, $e->getDetails());
        /** @var list<array{field?: string, code?: string, message?: string, index?: int}> $issues */
        $issues = $details['errors'] ?? [];
        self::assertCount(count($issues), $e->getFieldErrors());
        foreach ($issues as $i => $issue) {
            $fe = $e->getFieldErrors()[$i];
            self::assertSame($issue['field'] ?? null, $fe->field);
            self::assertSame($issue['code'] ?? null, $fe->code);
            self::assertSame($issue['message'] ?? null, $fe->message);
            self::assertSame($issue['index'] ?? null, $fe->index);
        }
        self::assertSame($details['wallet'] ?? null, $e->getWallet());
        $retryAfter = $res['headers']['Retry-After'] ?? null;
        self::assertSame($retryAfter === null ? null : (float) $retryAfter, $e->getRetryAfter());
    }
}
