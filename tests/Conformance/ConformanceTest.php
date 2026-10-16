<?php

declare(strict_types=1);

namespace StampMitra\Tests\Conformance;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StampMitra\Exception\AuthenticationError;
use StampMitra\Exception\ConflictError;
use StampMitra\Exception\ConnectionError;
use StampMitra\Exception\NotFoundError;
use StampMitra\Exception\PaymentRequiredError;
use StampMitra\Exception\PermissionError;
use StampMitra\Exception\RateLimitError;
use StampMitra\Exception\StampMitraError;
use StampMitra\Exception\TimeoutError;
use StampMitra\Exception\UpstreamError;
use StampMitra\Exception\ValidationError;
use StampMitra\Http\RequestOptions;
use StampMitra\StampMitra;
use StampMitra\Support\Validators;
use StampMitra\Webhook;

/**
 * Black-box conformance run against the spec repo's mock server
 * (stampmitra-developer-openapi/scripts/mock-server.mjs).
 *
 *   node <openapi-repo>/scripts/mock-server.mjs --port 4010 &
 *   MOCK_SERVER_URL=http://127.0.0.1:4010 composer test:conformance
 *
 * Skipped when MOCK_SERVER_URL is unset, unless CONFORMANCE_REQUIRED=1 (CI), where it fails instead.
 */
final class ConformanceTest extends TestCase
{
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
        $this->mockPost('/__mock/reset');
    }

    // ---- helpers -------------------------------------------------------

    private function mockPost(string $path): void
    {
        file_get_contents($this->url . $path, false, stream_context_create(['http' => ['method' => 'POST', 'content' => '', 'header' => "Content-Length: 0\r\n"]]));
    }

    /** @return list<array{method: string, path: string, headers: array<string, string>, body: mixed}> */
    private function recorded(): array
    {
        /** @var list<array{method: string, path: string, headers: array<string, string>, body: mixed}> $r */
        $r = json_decode((string) file_get_contents($this->url . '/__mock/requests'), true, 512, JSON_THROW_ON_ERROR);

        return $r;
    }

    /** @param array<string, mixed> $extra */
    private function client(array $extra = []): StampMitra
    {
        return new StampMitra(...($extra + [
            'apiKey' => self::KEY,
            'baseUrl' => $this->url,
            'maxRetries' => 2,
            'sleeper' => function (float $s): void {
                $this->sleeps[] = $s;
            },
        ]));
    }

    /** @return array<string, array<string, string>> */
    private static function scenario(string $s): array
    {
        return ['headers' => ['x-mock-scenario' => $s]];
    }

    // ---- 31 operations -------------------------------------------------

    /** @return array<string, array{callable(StampMitra): mixed, string, string}> */
    public static function operations(): array
    {
        $order = ['externalOrderId' => 'o1', 'externalCustomerId' => 'c1', 'serviceCode' => 'estamp.instrument.rent_agreement'];

        return [
            'verify.pan' => [fn (StampMitra $c) => $c->verify->pan('ABCDE1234F', 'Rahul Kumar'), 'POST', '/v1/verify/pan'],
            'verify.gstin' => [fn (StampMitra $c) => $c->verify->gstin('27ABCDE1234F1Z5'), 'POST', '/v1/verify/gstin'],
            'verify.rc' => [fn (StampMitra $c) => $c->verify->rc('MH12AB1234'), 'POST', '/v1/verify/rc'],
            'verify.voterId' => [fn (StampMitra $c) => $c->verify->voterId('ABC1234567'), 'POST', '/v1/verify/voter-id'],
            'verify.drivingLicense' => [fn (StampMitra $c) => $c->verify->drivingLicense('MH1420110012345', '1990-01-31'), 'POST', '/v1/verify/driving-license'],
            'verify.passport' => [fn (StampMitra $c) => $c->verify->passport('PA1079341954215', '1990-01-31'), 'POST', '/v1/verify/passport'],
            'verify.cin' => [fn (StampMitra $c) => $c->verify->cin('U74999DL2015PTC284009'), 'POST', '/v1/verify/cin'],
            'verify.udyam' => [fn (StampMitra $c) => $c->verify->udyam('UDYAM-MH-12-1234567'), 'POST', '/v1/verify/udyam'],
            'verify.panToGstin' => [fn (StampMitra $c) => $c->verify->panToGstin('ABCDE1234F'), 'POST', '/v1/verify/pan-to-gstin'],
            'verify.panToUdyam' => [fn (StampMitra $c) => $c->verify->panToUdyam('ABCDE1234F'), 'POST', '/v1/verify/pan-to-udyam'],
            'verify.bankAccount' => [fn (StampMitra $c) => $c->verify->bankAccount('000123456789', 'ICIC0001234'), 'POST', '/v1/verify/bank-account'],
            'services.list' => [fn (StampMitra $c) => $c->services->list(), 'GET', '/v1/services'],
            'services.get' => [fn (StampMitra $c) => $c->services->get('estamp.instrument.rent_agreement'), 'GET', '/v1/services/estamp.instrument.rent_agreement'],
            'estamp.getSchema' => [fn (StampMitra $c) => $c->estamp->getSchema('estamp.instrument.rent_agreement'), 'GET', '/v1/estamp/schema/estamp.instrument.rent_agreement'],
            'estamp.listStates' => [fn (StampMitra $c) => $c->estamp->listStates(), 'GET', '/v1/estamp/states'],
            'estamp.listInstruments' => [fn (StampMitra $c) => $c->estamp->listInstruments('MH'), 'GET', '/v1/estamp/states/MH/instruments'],
            'estamp.listArticles' => [fn (StampMitra $c) => $c->estamp->listArticles('rent_agreement'), 'GET', '/v1/estamp/instruments/rent_agreement/articles'],
            'estamp.calculateDuty' => [fn (StampMitra $c) => $c->estamp->calculateDuty(['stateCode' => 'MH', 'articleCode' => '5']), 'POST', '/v1/estamp/duty/calculate'],
            'estamp.addParties' => [fn (StampMitra $c) => $c->estamp->addParties('ord_123', ['customerName' => 'A', 'partyName' => 'B']), 'POST', '/v1/estamp/orders/ord_123/parties'],
            'orders.create' => [fn (StampMitra $c) => $c->orders->create($order), 'POST', '/v1/orders'],
            'orders.get' => [fn (StampMitra $c) => $c->orders->get('o1'), 'GET', '/v1/orders/o1'],
            'orders.getStatus' => [fn (StampMitra $c) => $c->orders->getStatus('o1'), 'GET', '/v1/orders/o1/status'],
            'orders.getPayment' => [fn (StampMitra $c) => $c->orders->getPayment('o1'), 'GET', '/v1/orders/o1/payment'],
            'orders.listDocuments' => [fn (StampMitra $c) => $c->orders->listDocuments('o1'), 'GET', '/v1/orders/o1/documents'],
            'orders.getDocumentDownload' => [fn (StampMitra $c) => $c->orders->getDocumentDownload('o1', 'doc_1'), 'GET', '/v1/orders/o1/documents/doc_1/download'],
            'payments.intents.create' => [fn (StampMitra $c) => $c->payments->intents->create(['externalOrderId' => 'o1', 'amountMinor' => 50000, 'currency' => 'INR']), 'POST', '/v1/payments/intents'],
            'payments.intents.get' => [fn (StampMitra $c) => $c->payments->intents->get('pi_1'), 'GET', '/v1/payments/intents/pi_1'],
            'usage.summary' => [fn (StampMitra $c) => $c->usage->summary(), 'GET', '/v1/usage/summary'],
            'logs.list' => [fn (StampMitra $c) => $c->logs->list(['limit' => 50]), 'GET', '/v1/logs'],
            'webhooks.events.list' => [fn (StampMitra $c) => $c->webhooks->events->list(limit: 20), 'GET', '/v1/webhooks/events'],
            'webhooks.events.replay' => [fn (StampMitra $c) => $c->webhooks->events->replay('evt_1'), 'POST', '/v1/webhooks/events/evt_1/replay'],
        ];
    }

    /** @param callable(StampMitra): mixed $call */
    #[DataProvider('operations')]
    public function testEveryOperationHitsTheRightRouteAndSucceeds(callable $call, string $method, string $path): void
    {
        $client = $this->client();
        $result = $call($client);
        self::assertNotNull($result);

        $seen = $this->recorded();
        self::assertCount(1, $seen);
        self::assertSame($method, $seen[0]['method']);
        self::assertSame($path, $seen[0]['path']);
        self::assertSame('Bearer ' . self::KEY, $seen[0]['headers']['authorization']);
        self::assertStringStartsWith('stampmitra-php/', $seen[0]['headers']['user-agent']);
        if ($method === 'POST' && $path !== '/v1/webhooks/events/evt_1/replay') {
            self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $seen[0]['headers']['idempotency-key']);
        } elseif ($method === 'GET') {
            self::assertArrayNotHasKey('idempotency-key', $seen[0]['headers']);
        }
    }

    public function testThereAreExactlyThirtyOneOperations(): void
    {
        self::assertCount(31, self::operations());
    }

    // ---- error mapping -------------------------------------------------

    /** @return array<int, array{int, class-string<StampMitraError>}> */
    public static function errorStatuses(): array
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
            '503' => [503, UpstreamError::class],
            '504' => [504, UpstreamError::class],
        ];
    }

    /** @param class-string<StampMitraError> $class */
    #[DataProvider('errorStatuses')]
    public function testErrorStatusMapping(int $status, string $class): void
    {
        try {
            $this->client(['maxRetries' => 0])->usage->summary(self::scenario("error-$status"));
            self::fail('expected exception');
        } catch (StampMitraError $e) {
            self::assertInstanceOf($class, $e);
            self::assertSame($status, $e->getStatus());
            self::assertNotNull($e->getErrorCode());
            self::assertNotEmpty($e->getRequestId());
            self::assertStringNotContainsString(self::KEY, (string) $e);
        }
    }

    public function testRateLimitWithRetryAfterHeader(): void
    {
        try {
            $this->client(['maxRetries' => 0])->usage->summary(self::scenario('retry-after:7'));
            self::fail('expected exception');
        } catch (RateLimitError $e) {
            self::assertSame(7.0, $e->getRetryAfter());
        }
    }

    public function testRetryAfterOverridesBackoffAndIsCapped(): void
    {
        $this->client()->usage->summary(self::scenario('seq:ra:503,200'));
        self::assertCount(1, $this->sleeps);
        self::assertLessThanOrEqual(0.5, $this->sleeps[0]);

        $this->mockPost('/__mock/reset');
        $this->sleeps = [];
        try {
            $this->client(['maxRetries' => 1])->usage->summary(self::scenario('retry-after:3'));
            self::fail('expected exception');
        } catch (RateLimitError $e) {
            self::assertSame([3.0], $this->sleeps);
            self::assertCount(2, $this->recorded());
        }
    }

    public function testRateLimitWithoutRetryAfterHeaderUsesBackoff(): void
    {
        // A documented 429 fixture (GET /v1/estamp/states) is served without Retry-After.
        try {
            $this->client(['maxRetries' => 1])->estamp->listStates(self::scenario('error-429'));
            self::fail('expected exception');
        } catch (RateLimitError $e) {
            self::assertNull($e->getRetryAfter());
            self::assertCount(1, $this->sleeps);
            self::assertLessThanOrEqual(0.5, $this->sleeps[0]);
        }
    }

    // ---- retry-scenarios.json -------------------------------------------

    /** @return array<string, array{array<string, mixed>}> */
    public static function retryScenarios(): array
    {
        $file = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/retry-scenarios.json'), true, 512, JSON_THROW_ON_ERROR);
        $out = [];
        foreach ($file['scenarios'] as $s) {
            $out[$s['name']] = [$s];
        }

        return $out;
    }

    /**
     * Drives the SDK transport with the shared scenarios. Scenarios containing a client-side
     * "timeout" step cannot be produced by the mock sequence and are covered by testTimeout*().
     *
     * @param array<string, mixed> $s
     */
    #[DataProvider('retryScenarios')]
    public function testSharedRetryScenario(array $s): void
    {
        /** @var list<mixed> $sequence */
        $sequence = $s['sequence'];
        $items = [];
        foreach ($sequence as $item) {
            if ($item === 'timeout') {
                self::markTestSkipped('timeout step: see testTimeout*');
            }
            $items[] = $item === 'connection_error' ? 'conn' : (string) (is_array($item) ? $item['status'] : $item);
        }
        if (($s['optional'] ?? false) === true) {
            self::markTestSkipped('optional scenario');
        }

        $maxRetries = match ($s['name']) {
            'get-max-retries-zero' => 0,
            'get-max-retries-five' => 5,
            default => 2,
        };
        $key = 'k' . bin2hex(random_bytes(4));
        $headers = ['x-mock-scenario' => "seq:$key:" . implode(',', $items)];
        $options = ['headers' => $headers];
        $auto = false;
        if ($s['idempotencyKey'] === true) {
            $options['idempotencyKey'] = 'caller-key-1';
        } elseif ($s['idempotencyKey'] === 'auto') {
            $auto = true;
        }

        $client = $this->client(['maxRetries' => $maxRetries]);
        $transport = (new \ReflectionProperty($client->usage, 'transport'))->getValue($client->usage);
        self::assertInstanceOf(\StampMitra\Http\Transport::class, $transport);
        $path = (string) $s['path'];
        $body = $s['method'] === 'GET' ? null : [];
        $outcome = $s['outcome'];
        $status = null;
        $thrown = null;
        try {
            $status = $transport->request((string) $s['method'], $path, $body, [], RequestOptions::from($options), $auto)->status;
        } catch (StampMitraError $e) {
            $thrown = $e;
        }

        if (is_int($outcome)) {
            self::assertNull($thrown, $thrown?->getMessage() ?? '');
            // Some mock success fixtures answer 201 where the scenario file says 200: any 2xx is a success.
            self::assertSame(2, intdiv((int) $status, 100));
        } else {
            self::assertNotNull($thrown, "expected $outcome");
            $class = (string) preg_replace('/\(.*$/', '', (string) $outcome);
            self::assertSame($class, (new \ReflectionClass($thrown))->getShortName());
            if (preg_match('/\((\d{3})\)/', (string) $outcome, $m) === 1) {
                self::assertSame((int) $m[1], $thrown->getStatus());
            }
        }

        $mine = array_values(array_filter($this->recorded(), static fn (array $r): bool => $r['path'] === $path));
        self::assertCount((int) $s['expectedAttempts'], $mine, $s['name']);
        if (($s['sameIdempotencyKeyAcrossAttempts'] ?? false) === true) {
            $keys = array_unique(array_map(static fn (array $r): string => $r['headers']['idempotency-key'] ?? '', $mine));
            self::assertCount(1, $keys);
            self::assertNotSame('', $keys[0]);
        }
        if ($s['idempotencyKey'] === false) {
            foreach ($mine as $r) {
                self::assertArrayNotHasKey('idempotency-key', $r['headers']);
            }
        }
    }

    public function testIdempotencyKeyReusedAcrossRetriesViaResourceMethods(): void
    {
        $this->client()->verify->pan('ABCDE1234F', null, self::scenario('seq:idem:503,502,200'));
        $reqs = $this->recorded();
        self::assertCount(3, $reqs);
        $keys = array_unique(array_map(static fn (array $r): string => $r['headers']['idempotency-key'], $reqs));
        self::assertCount(1, $keys);

        $this->mockPost('/__mock/reset');
        $this->client()->orders->create(['externalOrderId' => 'o', 'externalCustomerId' => 'c', 'serviceCode' => 's'], ['idempotencyKey' => 'mine-1', 'headers' => ['x-mock-scenario' => 'seq:idem2:503,200']]);
        $reqs = $this->recorded();
        self::assertCount(2, $reqs);
        self::assertSame(['mine-1', 'mine-1'], array_map(static fn (array $r): string => $r['headers']['idempotency-key'], $reqs));
    }

    // ---- transport failures --------------------------------------------

    public function testTimeoutRaisesTimeoutErrorWithGuzzlePerCallTimeout(): void
    {
        $client = $this->client(['maxRetries' => 0, 'timeout' => 30.0]);
        $start = microtime(true);
        try {
            $client->usage->summary(['timeout' => 0.3, 'headers' => ['x-mock-scenario' => 'delay:1500']]);
            self::fail('expected TimeoutError');
        } catch (TimeoutError $e) {
            self::assertInstanceOf(ConnectionError::class, $e);
            self::assertLessThan(1.2, microtime(true) - $start, 'per-call timeout must override the 30s default');
        }
    }

    public function testTimeoutIsRetriedForGetThenRaises(): void
    {
        try {
            $this->client(['maxRetries' => 1])->usage->summary(['timeout' => 0.2, 'headers' => ['x-mock-scenario' => 'delay:1000']]);
            self::fail('expected TimeoutError');
        } catch (TimeoutError) {
            self::assertCount(2, $this->recorded());
        }
    }

    public function testConnectionRefused(): void
    {
        $client = new StampMitra(self::KEY, 'http://127.0.0.1:1', maxRetries: 1, sleeper: function (float $s): void {
            $this->sleeps[] = $s;
        });
        try {
            $client->usage->summary();
            self::fail('expected ConnectionError');
        } catch (ConnectionError $e) {
            self::assertNotInstanceOf(TimeoutError::class, $e);
            self::assertCount(1, $this->sleeps);
            self::assertStringNotContainsString(self::KEY, $e->getMessage());
        }
    }

    public function testConnectionDroppedMidRequestIsRetriedForGet(): void
    {
        $this->client()->usage->summary(self::scenario('seq:drop:conn,200'));
        self::assertCount(2, $this->recorded());
    }

    // ---- bodies the mock cannot produce (real Guzzle stack + MockHandler) ----

    /** @param list<Response> $responses */
    private function guzzleClient(array $responses): StampMitra
    {
        $http = new GuzzleClient(['handler' => HandlerStack::create(new MockHandler($responses))]);

        return new StampMitra(self::KEY, $this->url, maxRetries: 0, httpClient: $http);
    }

    public function testNonJsonErrorBodyStillMapsByStatus(): void
    {
        foreach ([[502, UpstreamError::class], [429, RateLimitError::class], [404, NotFoundError::class]] as [$status, $class]) {
            try {
                $this->guzzleClient([new Response($status, ['Content-Type' => 'text/html'], '<html>Bad Gateway</html>')])->usage->summary();
                self::fail('expected exception');
            } catch (StampMitraError $e) {
                self::assertInstanceOf($class, $e);
                self::assertSame($status, $e->getStatus());
            }
        }
    }

    public function testUnknownExtraResponseFieldsAreTolerated(): void
    {
        $body = (string) json_encode(['referenceId' => 'SMV-1', 'valid' => true, 'brandNewField' => ['x' => 1], 'registeredName' => 'ACME']);
        $res = $this->guzzleClient([new Response(200, ['Content-Type' => 'application/json'], $body)])->verify->pan('ABCDE1234F');
        self::assertTrue($res->valid);
        self::assertSame(['x' => 1], $res->get('brandNewField'));
        self::assertArrayHasKey('brandNewField', $res->raw());
    }

    public function testLogsPaginationAcrossPages(): void
    {
        $page = static fn (string $id, ?string $next): Response => new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'items' => [['id' => $id, 'createdAt' => '2026-10-15T09:41:07.000Z', 'service' => 'pan.verify']],
            'nextCursor' => $next,
        ]));
        $client = $this->guzzleClient([$page('a', 'c1'), $page('b', 'c2'), $page('c', null)]);
        $ids = [];
        foreach ($client->logs->iterate(['limit' => 1]) as $entry) {
            $ids[] = $entry->id;
        }
        self::assertSame(['a', 'b', 'c'], $ids);
    }

    public function testLogsPaginationAgainstMock(): void
    {
        $ids = [];
        foreach ($this->client()->logs->iterate(['limit' => 50]) as $entry) {
            $ids[] = $entry->id;
        }
        self::assertNotEmpty($ids);
        self::assertCount(1, $this->recorded()); // fixture has nextCursor=null: exactly one page
    }

    // ---- shared fixtures -----------------------------------------------

    /** @return array<string, array{array<string, mixed>, int}> */
    public static function webhookVectors(): array
    {
        $file = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/webhook-signatures.json'), true, 512, JSON_THROW_ON_ERROR);
        $out = [];
        foreach ($file['vectors'] as $v) {
            $out[$v['name']] = [$v, $file['now']];
        }

        return $out;
    }

    /** @param array<string, mixed> $v */
    #[DataProvider('webhookVectors')]
    public function testWebhookSignatureVectors(array $v, int $now): void
    {
        $reason = Webhook::failureReason($v['payload'], $v['timestamp'], $v['signature'], $v['secret'], 300, $now);
        if ($v['expected']['valid']) {
            self::assertNull($reason, $v['name']);
        } else {
            self::assertSame($v['expected']['reason'], $reason, $v['name']);
        }
    }

    /** @return array<string, array{string, string, bool}> */
    public static function validatorVectors(): array
    {
        $file = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/validators.json'), true, 512, JSON_THROW_ON_ERROR);
        $method = ['passport' => 'passportFileNumber'];
        $out = [];
        foreach ($file['validators'] as $name => $def) {
            foreach ($def['valid'] as $i => $input) {
                $out["$name valid #$i"] = [$method[$name] ?? $name, $input, true];
            }
            foreach ($def['invalid'] as $i => $input) {
                $out["$name invalid #$i"] = [$method[$name] ?? $name, $input, false];
            }
        }

        return $out;
    }

    #[DataProvider('validatorVectors')]
    public function testValidatorVectors(string $method, string $input, bool $valid): void
    {
        $fn = [Validators::class, $method];
        self::assertIsCallable($fn);
        if (!$valid) {
            $this->expectException(ValidationError::class);
        }
        $out = $fn($input);
        self::assertIsString($out);
    }
}
