<?php

declare(strict_types=1);

namespace StampMitra\Tests\Support;

use StampMitra\StampMitra;

/**
 * Maps every contract-1.2.0 e-Stamp / wallet / account operation to the SDK call that
 * serves it, and loads the golden request/response fixtures for them.
 */
final class FixtureOperations
{
    /**
     * Each callable receives the client, the path parameters (in path order), the fixture
     * request, and the per-request options (headers, idempotencyKey, ...).
     *
     * @return array<string, callable(StampMitra, list<string>, array<string, mixed>, array<string, mixed>): mixed>
     */
    public static function calls(): array
    {
        $body = static fn (array $r): array => is_array($r['body'] ?? null) ? $r['body'] : [];
        $q = static fn (array $r): array => is_array($r['query'] ?? null) ? $r['query'] : [];
        $int = static fn (mixed $v): ?int => $v === null ? null : (int) $v;
        $pan = static function (array $r): array {
            /** @var list<array<string, string>> $parts */
            $parts = $r['multipart']['parts'];
            $file = [];
            $type = 'pan_card';
            foreach ($parts as $part) {
                if ($part['name'] === 'file') {
                    $file = ['contents' => (string) $part['content'], 'filename' => (string) $part['filename'], 'contentType' => (string) $part['contentType']];
                } elseif ($part['name'] === 'type') {
                    $type = (string) $part['value'];
                }
            }

            return [$file, $type];
        };

        return [
            'GET /v1/estamp/states/availability' => fn ($c, $p, $r, $o) => $c->estamp->getStatesAvailability(isset($q($r)['active']) ? $q($r)['active'] === 'true' : null, $o),
            'GET /v1/estamp/states/{stateCode}' => fn ($c, $p, $r, $o) => $c->estamp->getState($p[0], $o),
            'GET /v1/estamp/states/{stateCode}/requirements' => fn ($c, $p, $r, $o) => $c->estamp->getStateRequirements($p[0], $q($r)['articleCode'] ?? null, isset($q($r)['denomination']) ? (float) $q($r)['denomination'] : null, $q($r)['category'] ?? null, $o),
            'GET /v1/estamp/states/{stateCode}/districts' => fn ($c, $p, $r, $o) => $c->estamp->listDistricts($p[0], $o),
            'GET /v1/estamp/states/{stateCode}/districts/{districtCode}/sros' => fn ($c, $p, $r, $o) => $c->estamp->listSros($p[0], $p[1], $o),
            'POST /v1/estamp/orders/validate' => fn ($c, $p, $r, $o) => $c->estamp->orders->validate($body($r), $o),
            'POST /v1/estamp/orders' => fn ($c, $p, $r, $o) => $c->estamp->orders->create($body($r), $o),
            'GET /v1/estamp/orders' => fn ($c, $p, $r, $o) => $c->estamp->orders->list($int($q($r)['page'] ?? null), $int($q($r)['limit'] ?? null), $q($r)['cursor'] ?? null, $o),
            'GET /v1/estamp/orders/{id}' => fn ($c, $p, $r, $o) => $c->estamp->orders->get($p[0], $o),
            'POST /v1/estamp/orders/{id}/cancel' => fn ($c, $p, $r, $o) => $c->estamp->orders->cancel($p[0], $o),
            'GET /v1/estamp/orders/{id}/export' => fn ($c, $p, $r, $o) => $c->estamp->orders->export($p[0], $o),
            'GET /v1/estamp/orders/{id}/stamps' => fn ($c, $p, $r, $o) => $c->estamp->orders->listStamps($p[0], $o),
            'POST /v1/estamp/orders/{orderId}/documents' => function ($c, $p, $r, $o) use ($pan) {
                [$file, $type] = $pan($r);

                return $c->estamp->orders->uploadDocument($p[0], $file, $type, $o);
            },
            'GET /v1/estamp/stamps/{stampId}' => fn ($c, $p, $r, $o) => $c->estamp->stamps->get($p[0], $o),
            'POST /v1/estamp/stamps/{stampId}/cancel' => fn ($c, $p, $r, $o) => $c->estamp->stamps->cancel($p[0], $o),
            'POST /v1/estamp/stamps/{stampId}/replace' => fn ($c, $p, $r, $o) => $c->estamp->stamps->replace($p[0], $o),
            'POST /v1/estamp/stamps/{stampId}/pan-card' => fn ($c, $p, $r, $o) => $c->estamp->stamps->setPanCard($p[0], (string) $body($r)['panCardImage'], $o),
            'GET /v1/estamp/stamps/{stampId}/certificate' => fn ($c, $p, $r, $o) => $c->estamp->stamps->getCertificate($p[0], $o),
            'GET /v1/estamp/wallet' => fn ($c, $p, $r, $o) => $c->estamp->getWallet($o),
            'GET /v1/stamp-duty-wallet' => fn ($c, $p, $r, $o) => $c->stampDutyWallet->balance($o),
            'POST /v1/stamp-duty-wallet/topup' => fn ($c, $p, $r, $o) => $c->stampDutyWallet->topup($body($r)['baseAmount'], $o),
            'GET /v1/stamp-duty-wallet/transactions' => fn ($c, $p, $r, $o) => $c->stampDutyWallet->listTransactions($int($q($r)['limit'] ?? null), $q($r)['cursor'] ?? null, $o),
            'GET /v1/stamp-duty-wallet/invoices' => fn ($c, $p, $r, $o) => $c->stampDutyWallet->listInvoices($int($q($r)['limit'] ?? null), $q($r)['cursor'] ?? null, $o),
            'GET /v1/stamp-duty-wallet/invoices/{id}' => fn ($c, $p, $r, $o) => $c->stampDutyWallet->getInvoice($p[0], $o),
            'GET /v1/service-fee-wallet' => fn ($c, $p, $r, $o) => $c->serviceFeeWallet->balance($o),
            'POST /v1/service-fee-wallet/topup' => fn ($c, $p, $r, $o) => $c->serviceFeeWallet->topup($body($r)['baseAmount'], $o),
            'GET /v1/service-fee-wallet/transactions' => fn ($c, $p, $r, $o) => $c->serviceFeeWallet->listTransactions($int($q($r)['limit'] ?? null), $q($r)['cursor'] ?? null, $o),
            'GET /v1/service-fee-wallet/invoices' => fn ($c, $p, $r, $o) => $c->serviceFeeWallet->listInvoices($int($q($r)['limit'] ?? null), $q($r)['cursor'] ?? null, $o),
            'GET /v1/service-fee-wallet/invoices/{id}' => fn ($c, $p, $r, $o) => $c->serviceFeeWallet->getInvoice($p[0], $o),
            'GET /v1/account/balance' => fn ($c, $p, $r, $o) => $c->account->balance($o),
            'GET /v1/account/usage' => fn ($c, $p, $r, $o) => $c->account->usage($o),
        ];
    }

    /**
     * @return list<array<string, mixed>> every vendored fixture of the operations in calls()
     */
    public static function fixtures(): array
    {
        $calls = self::calls();
        $out = [];
        $files = glob(dirname(__DIR__) . '/fixtures/operations/*.json') ?: [];
        sort($files);
        foreach ($files as $file) {
            /** @var array<string, mixed> $fx */
            $fx = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            /** @var array{method: string, path: string} $op */
            $op = $fx['operation'];
            if (isset($calls[$op['method'] . ' ' . $op['path']])) {
                $out[] = $fx;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed> the success fixture of an operation key such as "POST /v1/estamp/orders"
     */
    public static function successFixture(string $key): array
    {
        foreach (self::fixtures() as $fx) {
            /** @var array{method: string, path: string} $op */
            $op = $fx['operation'];
            if ($op['method'] . ' ' . $op['path'] === $key && $fx['scenario'] === 'success') {
                return $fx;
            }
        }
        throw new \LogicException("no success fixture for $key");
    }

    /** @return array{string, list<string>}|null the "METHOD template" key and the path parameter values */
    public static function route(string $method, string $path): ?array
    {
        $found = null;
        foreach (array_keys(self::calls()) as $key) {
            [$m, $template] = explode(' ', $key, 2);
            if ($m !== $method) {
                continue;
            }
            $re = '#^' . preg_replace('/\\\\\{\w+\\\\\}/', '([^/]+)', preg_quote($template, '#')) . '$#';
            if (preg_match((string) $re, $path, $match) === 1) {
                $params = array_map('rawurldecode', array_slice($match, 1));
                // literal segments (states/availability, orders/validate) beat {param} segments
                if ($found === null || count($params) < count($found[1])) {
                    $found = [$key, $params];
                }
            }
        }

        return $found;
    }

    /**
     * Same shape as the mock server records: [{name, filename?, contentType?, size, value?}].
     *
     * @return list<array{name: string, filename?: string, contentType?: string, size: int, value?: string}>
     */
    public static function parseMultipart(string $contentType, string $body): array
    {
        if (preg_match('/boundary="?([^";]+)"?/i', $contentType, $m) !== 1) {
            return [];
        }
        $out = [];
        foreach (explode('--' . $m[1], $body) as $chunk) {
            if (!str_starts_with($chunk, "\r\n")) {
                continue;
            }
            [$head, $data] = explode("\r\n\r\n", substr($chunk, 2), 2) + ['', ''];
            $data = substr($data, 0, -2); // trailing CRLF before the next boundary
            $part = ['name' => preg_match('/name="([^"]*)"/', $head, $n) === 1 ? $n[1] : '', 'size' => strlen($data)];
            if (preg_match('/filename="([^"]*)"/', $head, $f) === 1) {
                $part['filename'] = $f[1];
                if (preg_match('/^content-type:\s*(.+)$/im', $head, $ct) === 1) {
                    $part['contentType'] = trim($ct[1]);
                }
            } else {
                $part['value'] = $data;
            }
            $out[] = $part;
        }

        return $out;
    }
}
