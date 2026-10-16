<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use PHPUnit\Framework\TestCase;
use StampMitra\Exception\AuthenticationError;
use StampMitra\StampMitra;
use StampMitra\Tests\Support\FakeClient;

final class ClientTest extends TestCase
{
    public function testSuccessfulCallSendsExpectedRequestAndTypesResponse(): void
    {
        $fake = new FakeClient([FakeClient::json(200, [
            'referenceId' => 'SMV-1', 'pan' => 'ABCDE1234F', 'valid' => true, 'registeredName' => 'RAHUL KUMAR', 'futureField' => 'x',
        ], ['X-Request-Id' => 'req-1'])]);
        $client = $fake->client(['appInfo' => ['name' => 'myapp', 'version' => '2.0']]);

        $res = $client->verify->pan('abcde1234f', 'Rahul Kumar');

        self::assertSame('SMV-1', $res->referenceId);
        self::assertTrue($res->valid);
        self::assertSame('x', $res->raw()['futureField']);
        self::assertSame(200, $res->response()?->status);
        self::assertSame('req-1', $res->response()->requestId);

        $req = $fake->requests[0];
        self::assertSame('POST', $req->getMethod());
        self::assertSame('https://api-b2b.stampmitra.in/v1/verify/pan', (string) $req->getUri());
        self::assertSame('Bearer sk_test_abcdefgh12345678', $req->getHeaderLine('Authorization'));
        self::assertSame('application/json', $req->getHeaderLine('Content-Type'));
        self::assertSame('application/json', $req->getHeaderLine('Accept'));
        self::assertStringStartsWith('stampmitra-php/0.1.0 (PHP ', $req->getHeaderLine('User-Agent'));
        self::assertStringContainsString('myapp/2.0', $req->getHeaderLine('User-Agent'));
        self::assertSame('{"pan":"ABCDE1234F","name":"Rahul Kumar"}', $fake->lastBody());
        self::assertNotSame('', $req->getHeaderLine('Idempotency-Key'));
    }

    public function testEnvironmentAndKeyHygiene(): void
    {
        $fake = new FakeClient();
        self::assertSame('test', $fake->client()->environment);
        self::assertSame('live', $fake->client(['apiKey' => 'sk_live_abcdefgh12345678'])->environment);

        $this->expectException(AuthenticationError::class);
        $fake->client(['apiKey' => 'not-a-key']);
    }

    public function testCustomKeyAllowedWhenOptedIn(): void
    {
        $client = (new FakeClient())->client(['apiKey' => 'custom', 'allowCustomKey' => true]);
        self::assertSame('custom', $client->environment);
    }

    public function testMissingKeyRaises(): void
    {
        putenv('STAMPMITRA_API_KEY');
        $this->expectException(AuthenticationError::class);
        new StampMitra(httpClient: new FakeClient());
    }

    public function testKeyFromEnvironment(): void
    {
        putenv('STAMPMITRA_API_KEY=sk_test_fromenv12345');
        try {
            self::assertSame('test', (new StampMitra(httpClient: new FakeClient()))->environment);
        } finally {
            putenv('STAMPMITRA_API_KEY');
        }
    }

    public function testPerRequestHeadersAndCustomBaseUrl(): void
    {
        // The API returns a bare array for GET /v1/services.
        $fake = new FakeClient([FakeClient::json(200, [['serviceCode' => 'estamp.x', 'name' => 'X', 'isActive' => true, 'extra' => 1]])]);
        $client = $fake->client(['baseUrl' => 'https://sandbox.example.test/', 'defaultHeaders' => ['X-Default' => 'd']]);

        $list = $client->services->list(['headers' => ['X-Trace' => 't1']]);

        self::assertSame('estamp.x', $list->services[0]->serviceCode);
        self::assertTrue($list->services[0]->isActive);
        self::assertSame(1, $list->services[0]->raw()['extra']);
        self::assertSame('https://sandbox.example.test/v1/services', (string) $fake->requests[0]->getUri());
        self::assertSame('t1', $fake->requests[0]->getHeaderLine('X-Trace'));
        self::assertSame('d', $fake->requests[0]->getHeaderLine('X-Default'));
    }

    public function testPathParametersAreEncodedAndQueryIsBuilt(): void
    {
        $fake = new FakeClient([FakeClient::json(200, ['ok' => true]), FakeClient::json(200, ['items' => []])]);
        $client = $fake->client();

        $client->orders->getDocumentDownload('ord/1', 'doc 2');
        $client->webhooks->events->list(10, 'abc=');

        self::assertSame('/v1/orders/ord%2F1/documents/doc%202/download', $fake->requests[0]->getUri()->getPath());
        self::assertSame('limit=10&cursor=abc%3D', $fake->requests[1]->getUri()->getQuery());
    }

    public function testAny2xxIsSuccess(): void
    {
        $fake = new FakeClient([FakeClient::json(201, ['externalOrderId' => 'e1', 'createdAt' => '2026-10-16T00:00:00Z'])]);
        $order = $fake->client()->orders->create(['externalOrderId' => 'e1', 'externalCustomerId' => 'c1', 'serviceCode' => 's']);
        self::assertSame('e1', $order->externalOrderId);
        self::assertSame(201, $order->response()?->status);
    }

    public function testErrorRequestIdComesFromBodyWhenNoHeader(): void
    {
        $fake = new FakeClient([FakeClient::json(401, ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing required scope', 'requestId' => 'body-rid']])]);
        try {
            $fake->client()->usage->summary();
            self::fail('expected exception');
        } catch (AuthenticationError $e) {
            self::assertSame('body-rid', $e->getRequestId());
        }
    }

    public function testUsageSummaryTyped(): void
    {
        $fake = new FakeClient([FakeClient::json(200, ['totalApiRequests' => 5, 'callsByService' => ['pan.verify' => 5]])]);
        $usage = $fake->client()->usage->summary();
        self::assertSame(5, $usage->totalApiRequests);
        self::assertSame(['pan.verify' => 5], $usage->callsByService);
    }
}
