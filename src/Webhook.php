<?php

declare(strict_types=1);

namespace StampMitra;

use StampMitra\Exception\SignatureVerificationError;
use StampMitra\Model\WebhookEvent;

/**
 * Verifies webhook deliveries.
 *
 * Scheme: signature = hex(HMAC-SHA256(secret, "{timestamp}.{rawBody}")), where
 * rawBody is the request body exactly as received (do NOT decode and
 * re-encode it) and timestamp is unix seconds. Delivered headers:
 * X-Webhook-Signature, X-Webhook-Timestamp, X-Webhook-Event-Id.
 */
final class Webhook
{
    public const HEADER_SIGNATURE = 'X-Webhook-Signature';
    public const HEADER_TIMESTAMP = 'X-Webhook-Timestamp';
    public const HEADER_EVENT_ID = 'X-Webhook-Event-Id';
    public const DEFAULT_TOLERANCE = 300;

    /** Event names documented for order lifecycle webhooks. */
    public const EVENT_TYPES = [
        'order.created',
        'order.payment_confirmed',
        'order.fulfilment_started',
        'order.fulfilment_completed',
        'order.failed',
        'order.cancelled',
        'order.refund_completed',
        'order.document_ready',
    ];

    public const REASON_INVALID_TIMESTAMP = 'invalid_timestamp';
    public const REASON_OUTSIDE_TOLERANCE = 'timestamp_outside_tolerance';
    public const REASON_MISMATCH = 'signature_mismatch';

    /** Compute the expected signature (useful for tests and local replays). */
    public static function sign(string $payload, string $timestamp, string $secret): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    }

    /**
     * Why verification fails, or null when the delivery is authentic and fresh.
     *
     * @param int|null $now Unix time override for tests
     */
    public static function failureReason(
        string $payload,
        string $timestamp,
        string $signature,
        #[\SensitiveParameter]
        string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE,
        ?int $now = null,
    ): ?string {
        if ($secret === '') {
            throw new SignatureVerificationError('Webhook secret must not be empty', null, 'MISSING_SECRET');
        }
        if (preg_match('/^[0-9]{1,15}$/', $timestamp) !== 1) {
            return self::REASON_INVALID_TIMESTAMP;
        }
        if (abs(($now ?? time()) - (int) $timestamp) > $toleranceSeconds) {
            return self::REASON_OUTSIDE_TOLERANCE;
        }
        if (!hash_equals(self::sign($payload, $timestamp, $secret), $signature)) {
            return self::REASON_MISMATCH;
        }

        return null;
    }

    /** True when the signature matches and the timestamp is within tolerance. */
    public static function verifySignature(
        string $payload,
        string $timestamp,
        string $signature,
        string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE,
        ?int $now = null,
    ): bool {
        return self::failureReason($payload, $timestamp, $signature, $secret, $toleranceSeconds, $now) === null;
    }

    /**
     * Verify, then parse the payload into a typed event.
     *
     * @throws SignatureVerificationError when verification or JSON parsing fails
     */
    public static function constructEvent(
        string $payload,
        string $timestamp,
        string $signature,
        string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE,
        ?string $eventId = null,
        ?int $now = null,
    ): WebhookEvent {
        $reason = self::failureReason($payload, $timestamp, $signature, $secret, $toleranceSeconds, $now);
        if ($reason !== null) {
            throw new SignatureVerificationError("Webhook verification failed: $reason", null, strtoupper($reason));
        }
        $data = json_decode($payload, true);
        if (!is_array($data) || array_is_list($data)) {
            throw new SignatureVerificationError('Webhook payload is not a JSON object', null, 'INVALID_PAYLOAD');
        }
        if ($eventId !== null && !isset($data['eventId'])) {
            $data['eventId'] = $eventId;
        }

        /** @var array<string, mixed> $data */
        return WebhookEvent::fromArray($data);
    }

    /**
     * Convenience wrapper reading the three delivery headers (case-insensitive).
     *
     * @param array<string, string|list<string>> $headers
     */
    public static function constructEventFromHeaders(
        string $payload,
        array $headers,
        string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE,
        ?int $now = null,
    ): WebhookEvent {
        $find = static function (string $name) use ($headers): ?string {
            foreach ($headers as $key => $value) {
                if (strcasecmp((string) $key, $name) === 0) {
                    return is_array($value) ? ($value[0] ?? null) : $value;
                }
            }

            return null;
        };
        $signature = $find(self::HEADER_SIGNATURE);
        $timestamp = $find(self::HEADER_TIMESTAMP);
        if ($signature === null || $timestamp === null) {
            throw new SignatureVerificationError('Missing ' . self::HEADER_SIGNATURE . ' or ' . self::HEADER_TIMESTAMP . ' header', null, 'MISSING_HEADERS');
        }

        return self::constructEvent($payload, $timestamp, $signature, $secret, $toleranceSeconds, $find(self::HEADER_EVENT_ID), $now);
    }
}
