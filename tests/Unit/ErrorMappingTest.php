<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StampMitra\Exception\AuthenticationError;
use StampMitra\Exception\ConflictError;
use StampMitra\Exception\NotFoundError;
use StampMitra\Exception\PaymentRequiredError;
use StampMitra\Exception\PermissionError;
use StampMitra\Exception\RateLimitError;
use StampMitra\Exception\StampMitraError;
use StampMitra\Exception\UpstreamError;
use StampMitra\Exception\ValidationError;
use StampMitra\Tests\Support\FakeClient;

final class ErrorMappingTest extends TestCase
{
    /** @return array<int|string, array{int, string}> */
    public static function statuses(): array
    {
        return [
            '400' => [400, ValidationError::class],
            '401' => [401, AuthenticationError::class],
            '402' => [402, PaymentRequiredError::class],
            '403' => [403, PermissionError::class],
            '404' => [404, NotFoundError::class],
            '409' => [409, ConflictError::class],
            '429' => [429, RateLimitError::class],
            '500' => [500, UpstreamError::class],
            '502' => [502, UpstreamError::class],
            '418' => [418, StampMitraError::class],
        ];
    }

    #[DataProvider('statuses')]
    public function testStatusMapsToTypedException(int $status, string $class): void
    {
        $fake = new FakeClient([FakeClient::json($status, ['error' => ['code' => 'SOME_CODE', 'message' => 'boom', 'requestId' => 'rid-9', 'field' => 'pan']])]);
        $client = $fake->client(['maxRetries' => 0]);

        try {
            $client->usage->summary();
            self::fail('expected exception');
        } catch (StampMitraError $e) {
            self::assertSame($class, $e::class);
            self::assertSame($status, $e->getStatus());
            self::assertSame('SOME_CODE', $e->getErrorCode());
            self::assertSame('boom', $e->getMessage());
            self::assertSame('rid-9', $e->getRequestId());
            self::assertSame('pan', $e->getField());
            self::assertIsArray($e->getBody());
        }
    }

    public function testNonJsonErrorBodyStillMapsByStatus(): void
    {
        $fake = new FakeClient([new Response(502, [], '<html>Bad gateway</html>')]);
        try {
            $fake->client(['maxRetries' => 0])->usage->summary();
            self::fail('expected exception');
        } catch (UpstreamError $e) {
            self::assertSame('HTTP 502', $e->getMessage());
            self::assertSame('<html>Bad gateway</html>', $e->getBody());
        }
    }

    public function testRateLimitCarriesRetryAfter(): void
    {
        $fake = new FakeClient([FakeClient::json(429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'slow', 'requestId' => 'r']], ['Retry-After' => '7'])]);
        try {
            $fake->client(['maxRetries' => 0])->usage->summary();
            self::fail('expected exception');
        } catch (RateLimitError $e) {
            self::assertSame(7.0, $e->getRetryAfter());
        }
    }

    public function testPermissionMessageSurfacedVerbatim(): void
    {
        $msg = 'Sensitive verification requires an IP-allowlisted API key';
        $fake = new FakeClient([FakeClient::json(403, ['error' => ['code' => 'FORBIDDEN', 'message' => $msg, 'requestId' => 'r']])]);
        $this->expectException(PermissionError::class);
        $this->expectExceptionMessage($msg);
        $fake->client()->verify->passport('PA1079341954215', '1990-01-31');
    }
}
