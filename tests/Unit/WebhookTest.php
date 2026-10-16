<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StampMitra\Exception\SignatureVerificationError;
use StampMitra\Webhook;

final class WebhookTest extends TestCase
{
    /** @return array<string, array{array<string, mixed>, int}> */
    public static function vectors(): array
    {
        $file = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/webhook-signatures.json'), true, 512, JSON_THROW_ON_ERROR);
        $out = [];
        foreach ($file['vectors'] as $v) {
            $out[$v['name']] = [$v, $file['now']];
        }

        return $out;
    }

    /** @param array<string, mixed> $v */
    #[DataProvider('vectors')]
    public function testSharedFixtureVectors(array $v, int $now): void
    {
        $reason = Webhook::failureReason($v['payload'], $v['timestamp'], $v['signature'], $v['secret'], 300, $now);
        if ($v['expected']['valid']) {
            self::assertNull($reason);
            self::assertTrue(Webhook::verifySignature($v['payload'], $v['timestamp'], $v['signature'], $v['secret'], 300, $now));
        } else {
            self::assertSame($v['expected']['reason'], $reason);
            self::assertFalse(Webhook::verifySignature($v['payload'], $v['timestamp'], $v['signature'], $v['secret'], 300, $now));
        }
    }

    public function testEmptySecretIsRejectedNotSilentlyAccepted(): void
    {
        $payload = '{"coreOrderId":"core_1"}';
        $ts = (string) time();
        $this->expectException(SignatureVerificationError::class);
        Webhook::constructEvent($payload, $ts, Webhook::sign($payload, $ts, ''), '');
    }

    public function testConstructEventParsesTypedEvent(): void
    {
        $payload = '{"coreOrderId":"core_1","coreEventType":"FULFILMENT_COMPLETED","orderId":"ord_1","extra":1}';
        $ts = (string) time();
        $event = Webhook::constructEvent($payload, $ts, Webhook::sign($payload, $ts, 's3cret'), 's3cret', eventId: 'evt-7');

        self::assertSame('ord_1', $event->orderId);
        self::assertSame('FULFILMENT_COMPLETED', $event->coreEventType);
        self::assertSame('evt-7', $event->eventId);
        self::assertSame(1, $event->raw()['extra']);
    }

    public function testConstructEventRejectsBadSignature(): void
    {
        $this->expectException(SignatureVerificationError::class);
        Webhook::constructEvent('{}', (string) time(), 'deadbeef', 's3cret');
    }

    public function testConstructEventFromHeadersIsCaseInsensitive(): void
    {
        $payload = '{"orderId":"ord_2"}';
        $ts = (string) time();
        $event = Webhook::constructEventFromHeaders($payload, [
            'x-webhook-signature' => Webhook::sign($payload, $ts, 'k'),
            'X-WEBHOOK-TIMESTAMP' => [$ts],
        ], 'k');
        self::assertSame('ord_2', $event->orderId);
    }

    public function testMissingHeadersRaise(): void
    {
        $this->expectException(SignatureVerificationError::class);
        Webhook::constructEventFromHeaders('{}', [], 'k');
    }
}
