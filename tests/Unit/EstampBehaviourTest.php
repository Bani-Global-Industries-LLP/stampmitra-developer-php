<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StampMitra\Exception\ConflictError;
use StampMitra\Exception\PaymentRequiredError;
use StampMitra\Exception\StampMitraError;
use StampMitra\Exception\TimeoutError;
use StampMitra\Exception\UpstreamError;
use StampMitra\Exception\ValidationError;
use StampMitra\Model\EstampParty;
use StampMitra\Model\EstampPropertyArea;
use StampMitra\Model\EstampStampLine;
use StampMitra\StampMitra;
use StampMitra\Tests\Support\FakeClient;
use StampMitra\Tests\Support\FakeNetworkError;
use StampMitra\Tests\Support\FixtureOperations;

final class EstampBehaviourTest extends TestCase
{
    /** @param array<string, string> $headers */
    private function err(int $status, string $code = 'X', array $headers = []): \GuzzleHttp\Psr7\Response
    {
        return FakeClient::json($status, ['error' => ['code' => $code, 'message' => 'm', 'requestId' => 'r']], $headers);
    }

    /** @return array<string, mixed> */
    private function orderBody(): array
    {
        /** @var array<string, mixed> $b */
        $b = FixtureOperations::successFixture('POST /v1/estamp/orders')['request']['body'];

        return $b;
    }

    /** @return array<string, mixed> */
    private function created(): array
    {
        /** @var array<string, mixed> $b */
        $b = FixtureOperations::successFixture('POST /v1/estamp/orders')['response']['body'];

        return $b;
    }

    public function testCreateAutoGeneratesAKeyAndKeepsItAcrossRetries(): void
    {
        $fake = new FakeClient([$this->err(503), $this->err(429, 'X', ['Retry-After' => '2']), FakeClient::json(201, $this->created())]);
        $order = $fake->client()->estamp->orders->create($this->orderBody());
        self::assertSame('estord_9c1f2a3b', $order->id);
        self::assertCount(3, $fake->requests);
        $keys = array_unique(array_map(static fn ($r) => $r->getHeaderLine('Idempotency-Key'), $fake->requests));
        self::assertCount(1, $keys);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $keys[0]);
        self::assertSame(2.0, $fake->sleeps[1]);
    }

    public function testCreateUsesTheCallerKey(): void
    {
        $fake = new FakeClient([FakeClient::json(201, $this->created())]);
        $fake->client()->estamp->orders->create($this->orderBody(), ['idempotencyKey' => 'order-2026-0917']);
        self::assertSame('order-2026-0917', $fake->requests[0]->getHeaderLine('Idempotency-Key'));
    }

    public function testIdempotencyInProgressHonoursRetryAfterOnlyForThatCode(): void
    {
        $fake = new FakeClient([$this->err(409, 'IDEMPOTENCY_IN_PROGRESS', ['Retry-After' => '3']), FakeClient::json(201, $this->created())]);
        $fake->client()->estamp->orders->create($this->orderBody());
        self::assertCount(2, $fake->requests);
        self::assertSame([3.0], $fake->sleeps);

        $fake = new FakeClient([$this->err(409, 'CONFLICT', ['Retry-After' => '3']), FakeClient::json(201, $this->created())]);
        $this->expectException(ConflictError::class);
        try {
            $fake->client()->estamp->orders->create($this->orderBody());
        } finally {
            self::assertCount(1, $fake->requests);
        }
    }

    public function testRetryAfterIsCappedAndTimeoutsAreRetriedForKeyedCreate(): void
    {
        $fake = new FakeClient([$this->err(503, 'X', ['Retry-After' => '600']), new FakeNetworkError(new Request('POST', 'https://x.test'), 'cURL error 28: Operation timed out'), FakeClient::json(201, $this->created())]);
        $fake->client()->estamp->orders->create($this->orderBody());
        self::assertCount(3, $fake->requests);
        self::assertSame(60.0, $fake->sleeps[0]);
    }

    public function testPaymentRequiredExposesTheShortWallet(): void
    {
        $fake = new FakeClient([FakeClient::json(402, ['error' => ['code' => 'PAYMENT_REQUIRED', 'message' => 'low', 'details' => ['wallet' => 'SERVICE_FEE'], 'requestId' => 'r']])]);
        try {
            $fake->client()->estamp->orders->create($this->orderBody());
            self::fail('expected PaymentRequiredError');
        } catch (PaymentRequiredError $e) {
            self::assertSame('SERVICE_FEE', $e->getWallet());
            self::assertCount(1, $fake->requests);
        }
    }

    public function testValidationErrorExposesFieldErrors(): void
    {
        $fake = new FakeClient([FakeClient::json(400, ['error' => ['code' => 'BAD_REQUEST', 'message' => 'VALIDATION_FAILED', 'details' => ['errors' => [['field' => 'stamps[0].quantity', 'code' => 'OUT_OF_RANGE', 'message' => 'too many', 'index' => 0], ['field' => 'stamps[1].state', 'code' => 'MIXED_STATE', 'message' => 'm']]], 'requestId' => 'r']])]);
        try {
            $fake->client()->estamp->orders->validate($this->orderBody());
            self::fail('expected ValidationError');
        } catch (ValidationError $e) {
            $errors = $e->getFieldErrors();
            self::assertCount(2, $errors);
            self::assertSame('OUT_OF_RANGE', $errors[0]->code);
            self::assertSame(0, $errors[0]->index);
            self::assertNull($errors[1]->index);
            self::assertNull($e->getWallet());
        }
    }

    public function testValidateIsRetriedWithoutAKey(): void
    {
        $fake = new FakeClient([$this->err(503), FakeClient::json(200, ['valid' => true, 'status' => 'READY_TO_PLACE'])]);
        $res = $fake->client()->estamp->orders->validate($this->orderBody());
        self::assertTrue($res->valid);
        self::assertCount(2, $fake->requests);
        self::assertSame('', $fake->requests[0]->getHeaderLine('Idempotency-Key'));
    }

    /** @return array<string, array{callable(StampMitra, array<string, mixed>): mixed}> */
    public static function neverRetried(): array
    {
        return [
            'order cancel' => [fn (StampMitra $c, array $o) => $c->estamp->orders->cancel('o', $o)],
            'stamp cancel' => [fn (StampMitra $c, array $o) => $c->estamp->stamps->cancel('s', $o)],
            'stamp replace' => [fn (StampMitra $c, array $o) => $c->estamp->stamps->replace('s', $o)],
            'pan-card link' => [fn (StampMitra $c, array $o) => $c->estamp->stamps->setPanCard('s', 'https://example.com/p.jpg', $o)],
            'stamp duty top-up' => [fn (StampMitra $c, array $o) => $c->stampDutyWallet->topup(1000, $o)],
            'service fee top-up' => [fn (StampMitra $c, array $o) => $c->serviceFeeWallet->topup(1000, $o)],
            'webhook replay' => [fn (StampMitra $c, array $o) => $c->webhooks->events->replay('e', $o)],
        ];
    }

    /** @param callable(StampMitra, array<string, mixed>): mixed $call */
    #[DataProvider('neverRetried')]
    public function testNeverAutoRetriedWithOrWithoutACallerKey(callable $call): void
    {
        foreach ([[], ['idempotencyKey' => 'k']] as $options) {
            foreach ([$this->err(503, 'X', ['Retry-After' => '1']), $this->err(429, 'X', ['Retry-After' => '1']), new FakeNetworkError(new Request('POST', 'https://x.test'), 'cURL error 28: timed out')] as $failure) {
                $fake = new FakeClient([$failure, FakeClient::json(200, [])]);
                try {
                    $call($fake->client(), $options);
                    self::fail('expected an error');
                } catch (StampMitraError) {
                    self::assertCount(1, $fake->requests);
                    self::assertSame('', $fake->requests[0]->getHeaderLine('Idempotency-Key'));
                    self::assertSame([], $fake->sleeps);
                }
            }
        }
    }

    public function testTimeoutOnATopupSurfacesAsTimeoutError(): void
    {
        $fake = new FakeClient([new FakeNetworkError(new Request('POST', 'https://x.test'), 'cURL error 28: timed out')]);
        $this->expectException(TimeoutError::class);
        $fake->client()->stampDutyWallet->topup(500);
    }

    public function testTopupModelsKeepContractTypes(): void
    {
        $fake = new FakeClient([
            FakeClient::json(201, ['baseAmount' => 1000, 'processingFeePercent' => '6.00', 'processingFeeAmount' => '60.00', 'gstAmount' => 10.8, 'totalCharged' => 1070.8, 'creditAmount' => '1000.00', 'topupId' => 'cmw1']),
            FakeClient::json(201, ['baseAmount' => '1000.00', 'gstAmount' => '180.00', 'processingFeePercent' => '0.00', 'processingFeeAmount' => '0.00', 'creditAmount' => '1000.00', 'totalCharged' => '1180.00']),
        ]);
        $client = $fake->client();
        $sd = $client->stampDutyWallet->topup(1000);
        self::assertSame(1000.0, $sd->baseAmount);
        self::assertSame(10.8, $sd->gstAmount);
        self::assertSame(1070.8, $sd->totalCharged);
        self::assertSame('6.00', $sd->processingFeePercent);
        self::assertSame('1000.00', $sd->creditAmount);
        self::assertSame('cmw1', $sd->topupId);
        self::assertSame(['baseAmount' => 1000], json_decode($fake->lastBody(), true));
        $sf = $client->serviceFeeWallet->topup(1000);
        self::assertSame('1180.00', $sf->totalCharged);
        self::assertSame('180.00', $sf->gstAmount);
    }

    public function testStampLineObjectsSerialiseToTheContractShape(): void
    {
        $fake = new FakeClient([FakeClient::json(200, ['valid' => true])]);
        $line = new EstampStampLine(
            'MH',
            'ART1',
            500,
            new EstampParty('Jane Doe', 'Pune', gender: EstampParty::FEMALE),
            'Acme Pvt Ltd',
            clientReference: 'line-1',
            propertyArea: new EstampPropertyArea(EstampPropertyArea::SQUARE_FEET, 450),
            panCardImage: 'https://example.com/pan.jpg',
            quantity: 2,
        );
        $fake->client()->estamp->orders->validate(['reference' => 'r', 'stamps' => [$line]]);
        self::assertSame([
            'reference' => 'r',
            'stamps' => [[
                'clientReference' => 'line-1', 'state' => 'MH', 'articleCode' => 'ART1', 'denomination' => 500,
                'firstParty' => ['name' => 'Jane Doe', 'address' => 'Pune', 'gender' => 'FEMALE'],
                'secondParty' => 'Acme Pvt Ltd',
                'propertyArea' => ['unit' => 'Square Feet', 'value' => 450],
                'panCardImage' => 'https://example.com/pan.jpg', 'quantity' => 2,
            ]],
        ], json_decode($fake->lastBody(), true));
    }

    public function testLocalOrderValidation(): void
    {
        $client = (new FakeClient())->client();
        $line = ['state' => 'MH', 'articleCode' => 'A', 'denomination' => 1, 'firstParty' => 'x'];
        foreach ([
            'empty' => ['stamps' => []],
            'too many' => ['stamps' => array_fill(0, 5001, $line)],
            'quantity' => ['stamps' => [$line + ['quantity' => 1001]]],
            'mixed state' => ['stamps' => [$line, ['state' => 'KA'] + $line]],
        ] as $name => $body) {
            try {
                $client->estamp->orders->create($body);
                self::fail("expected ValidationError for $name");
            } catch (ValidationError $e) {
                self::assertNull($e->getStatus());
            }
        }
        $this->expectException(ValidationError::class);
        $client->estamp->stamps->setPanCard('s', 'http://example.com/pan.jpg');
    }

    public function testUploadRejectsUnsupportedAndOversizedFilesLocally(): void
    {
        $client = (new FakeClient())->client();
        foreach ([['contents' => 'GIF89a...', 'filename' => 'a.gif'], ['contents' => '%PDF' . str_repeat('x', 10 * 1024 * 1024), 'filename' => 'big.pdf']] as $file) {
            try {
                $client->estamp->orders->uploadDocument('o', $file);
                self::fail('expected ValidationError');
            } catch (ValidationError $e) {
                self::assertSame('file', $e->getField());
            }
        }
    }

    public function testMultipartBodyIsNotLogged(): void
    {
        $logs = new \ArrayObject();
        $logger = new class ($logs) extends \Psr\Log\AbstractLogger {
            /** @param \ArrayObject<int, string> $logs */
            public function __construct(private readonly \ArrayObject $logs)
            {
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->logs->append(json_encode($context, JSON_THROW_ON_ERROR));
            }
        };
        $fake = new FakeClient([FakeClient::json(201, ['documentId' => 'd'])]);
        $fake->client(['logger' => $logger])->estamp->orders->uploadDocument('o', ['contents' => '%PDF-SECRET-CONTENT', 'filename' => 'pan.pdf']);
        self::assertCount(2, $logs);
        self::assertStringNotContainsString('SECRET-CONTENT', implode('', $logs->getArrayCopy()));
    }

    public function testExhaustedGetRaisesUpstreamWithRetryAfterAccessor(): void
    {
        $fake = new FakeClient([$this->err(503, 'X', ['Retry-After' => '5']), $this->err(503, 'X', ['Retry-After' => '5'])]);
        try {
            $fake->client(['maxRetries' => 1])->account->balance();
            self::fail('expected UpstreamError');
        } catch (UpstreamError $e) {
            self::assertSame(5.0, $e->getRetryAfter());
        }
    }
}
