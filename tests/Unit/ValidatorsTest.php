<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StampMitra\Exception\ValidationError;
use StampMitra\Support\Validators as V;
use StampMitra\Tests\Support\FakeClient;

final class ValidatorsTest extends TestCase
{
    /** @return array<string, array{callable, string, string|null}> null expected = invalid */
    public static function cases(): array
    {
        return [
            'pan ok lower' => [[V::class, 'pan'], ' abcde1234f ', 'ABCDE1234F'],
            'pan short' => [[V::class, 'pan'], 'ABCDE123F', null],
            'pan digits first' => [[V::class, 'pan'], '1BCDE1234F', null],
            'gstin ok' => [[V::class, 'gstin'], '27ABCDE1234F1Z5', '27ABCDE1234F1Z5'],
            'gstin no Z' => [[V::class, 'gstin'], '27ABCDE1234F1A5', null],
            'ifsc ok' => [[V::class, 'ifsc'], 'icic0001234', 'ICIC0001234'],
            'ifsc no zero' => [[V::class, 'ifsc'], 'ICIC1001234', null],
            'cin ok' => [[V::class, 'cin'], 'U74999DL2015PTC284009', 'U74999DL2015PTC284009'],
            'cin bad prefix' => [[V::class, 'cin'], 'X74999DL2015PTC284009', null],
            'udyam ok' => [[V::class, 'udyam'], 'udyam-mh-12-1234567', 'UDYAM-MH-12-1234567'],
            'udyam bad' => [[V::class, 'udyam'], 'UDYAM-MH-12-123', null],
            'vehicle ok' => [[V::class, 'vehicleNumber'], 'MH-12 AB 1234', 'MH12AB1234'],
            'vehicle bad' => [[V::class, 'vehicleNumber'], '1234', null],
            'account ok' => [[V::class, 'bankAccount'], '000123456789', '000123456789'],
            'account 8 digits' => [[V::class, 'bankAccount'], '12345678', null],
            'account 19 digits' => [[V::class, 'bankAccount'], '1234567890123456789', null],
            'account letters' => [[V::class, 'bankAccount'], '12345678A', null],
            'passport ok' => [[V::class, 'passportFileNumber'], 'PA1079341954215', 'PA1079341954215'],
            'passport bad' => [[V::class, 'passportFileNumber'], 'P1079341954215', null],
            'dl ok' => [[V::class, 'drivingLicense'], 'MH1420110012345', 'MH1420110012345'],
            'dl bad' => [[V::class, 'drivingLicense'], 'MH14201100123', null],
            'voter ok' => [[V::class, 'voterId'], 'abc1234567', 'ABC1234567'],
            'voter bad' => [[V::class, 'voterId'], 'AB12345678', null],
            'phone ok' => [[V::class, 'phone'], '9876543210', '9876543210'],
            'phone bad' => [[V::class, 'phone'], '1234567890', null],
            'date ok' => [[V::class, 'date'], '1990-01-31', '1990-01-31'],
            'date bad' => [[V::class, 'date'], '31/01/1990', null],
        ];
    }

    /** @param callable(string): string $fn */
    #[DataProvider('cases')]
    public function testValidator(callable $fn, string $input, ?string $expected): void
    {
        if ($expected === null) {
            $this->expectException(ValidationError::class);
        }
        self::assertSame($expected, $fn($input));
    }

    public function testInvalidInputFailsLocallyWithNoNetworkCall(): void
    {
        $fake = new FakeClient();
        try {
            $fake->client()->verify->pan('nope');
            self::fail('expected exception');
        } catch (ValidationError $e) {
            self::assertSame('pan', $e->getField());
            self::assertNull($e->getStatus());
            self::assertCount(0, $fake->requests);
        }
    }

    public function testValidationCanBeDisabled(): void
    {
        $fake = new FakeClient([FakeClient::json(200, ['valid' => false])]);
        $fake->client(['validateInput' => false])->verify->pan('nope');
        self::assertCount(1, $fake->requests);
        self::assertSame('{"pan":"nope"}', $fake->lastBody());
    }

    public function testOrderCreateRequiresFields(): void
    {
        $fake = new FakeClient();
        $this->expectException(ValidationError::class);
        $fake->client()->orders->create(['externalOrderId' => 'x']);
    }
}
