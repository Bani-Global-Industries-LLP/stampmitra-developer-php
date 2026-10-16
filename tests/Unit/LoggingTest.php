<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use StampMitra\Tests\Support\FakeClient;

final class LoggingTest extends TestCase
{
    public function testLogsAreRedacted(): void
    {
        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $lines = [];

            /** @param mixed[] $context */
            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->lines[] = $message . ' ' . json_encode($context);
            }
        };
        $fake = new FakeClient([FakeClient::json(200, [
            'referenceId' => 'SMV-1', 'pan' => 'ABCDE1234F', 'registeredName' => 'RAHUL KUMAR', 'valid' => true,
        ])]);
        $fake->client(['logger' => $logger])->verify->pan('ABCDE1234F', 'Rahul Kumar');

        $all = implode("\n", $logger->lines);
        self::assertNotSame('', $all);
        self::assertStringNotContainsString('sk_test_abcdefgh12345678', $all);
        self::assertStringNotContainsString('ABCDE1234F', $all);
        self::assertStringNotContainsString('Rahul', $all);
        self::assertStringNotContainsString('RAHUL', $all);
        self::assertStringContainsString('[REDACTED]', $all);
        self::assertStringContainsString('******234F', $all);
        self::assertStringContainsString('SMV-1', $all);
    }

    public function testNothingIsLoggedByDefault(): void
    {
        $fake = new FakeClient([FakeClient::json(200, [])]);
        $fake->client()->usage->summary();
        self::assertCount(1, $fake->requests); // no logger configured: nothing to assert beyond not failing
    }
}
