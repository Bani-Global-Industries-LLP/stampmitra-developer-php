<?php

declare(strict_types=1);

namespace StampMitra\Support;

use StampMitra\Exception\ValidationError;

/**
 * Client-side input validators. The patterns mirror the API's DTO validators
 * so malformed input fails locally (no network call, nothing billed).
 */
final class Validators
{
    public const PAN = '/^[A-Z]{5}[0-9]{4}[A-Z]$/';
    public const GSTIN = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/';
    public const IFSC = '/^[A-Z]{4}0[A-Z0-9]{6}$/';
    public const CIN = '/^[LU][0-9]{5}[A-Z]{2}[0-9]{4}[A-Z]{3}[0-9]{6}$/';
    public const UDYAM = '/^UDYAM-[A-Z]{2}-[0-9]{2}-[0-9]{7}$/';
    public const VEHICLE_NUMBER = '/^[A-Z]{2}[0-9]{1,2}[A-Z]{0,3}[0-9]{4}$/';
    public const BANK_ACCOUNT = '/^[0-9]{9,18}$/';
    public const PASSPORT_FILE_NUMBER = '/^[A-Z]{2}[0-9]{13}$/';
    public const DRIVING_LICENSE = '/^[A-Z]{2}[0-9]{2}[0-9]{11}$/';
    public const VOTER_ID = '/^[A-Z]{3}[0-9]{7}$/';
    public const PHONE = '/^(?:\+?91|0)?[6-9][0-9]{9}$/';
    public const ISO_DATE = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}(?:[T ][0-9:.]+(?:Z|[+-][0-9:]+)?)?$/';

    public static function pan(string $value, string $field = 'pan'): string
    {
        return self::match($value, self::PAN, $field, 'must match the format ABCDE1234F');
    }

    public static function gstin(string $value, string $field = 'gstin'): string
    {
        return self::match($value, self::GSTIN, $field, 'must be a valid 15 character GSTIN, e.g. 27ABCDE1234F1Z5');
    }

    public static function ifsc(string $value, string $field = 'ifsc'): string
    {
        return self::match($value, self::IFSC, $field, 'must match the format ICIC0001234');
    }

    public static function cin(string $value, string $field = 'cin'): string
    {
        return self::match($value, self::CIN, $field, 'must match the format U74999DL2015PTC284009');
    }

    public static function udyam(string $value, string $field = 'udyamNumber'): string
    {
        return self::match($value, self::UDYAM, $field, 'must match the format UDYAM-XX-00-0000000');
    }

    /** Spaces and hyphens are stripped (MH-12 AB 1234 becomes MH12AB1234). */
    public static function vehicleNumber(string $value, string $field = 'vehicleNumber'): string
    {
        $clean = (string) preg_replace('/[\s-]+/', '', $value);

        return self::match($clean, self::VEHICLE_NUMBER, $field, 'must be an Indian vehicle registration number, e.g. MH12AB1234');
    }

    public static function bankAccount(string $value, string $field = 'accountNumber'): string
    {
        return self::match($value, self::BANK_ACCOUNT, $field, 'must be a 9-18 digit numeric bank account number');
    }

    public static function passportFileNumber(string $value, string $field = 'fileNumber'): string
    {
        return self::match($value, self::PASSPORT_FILE_NUMBER, $field, 'must match the format PA1079341954215');
    }

    public static function drivingLicense(string $value, string $field = 'dlNumber'): string
    {
        $clean = (string) preg_replace('/[\s-]+/', '', $value);

        return self::match($clean, self::DRIVING_LICENSE, $field, 'must match the format MH1420110012345');
    }

    public static function voterId(string $value, string $field = 'voterId'): string
    {
        return self::match($value, self::VOTER_ID, $field, 'must match the format ABC1234567');
    }

    public static function phone(string $value, string $field = 'phone'): string
    {
        $clean = trim($value);
        if (preg_match(self::PHONE, $clean) !== 1) {
            throw ValidationError::forField($field, "$field must be a valid 10-digit Indian mobile number");
        }

        return $clean;
    }

    public static function date(string $value, string $field = 'dob'): string
    {
        $clean = trim($value);
        if (preg_match(self::ISO_DATE, $clean) !== 1) {
            throw ValidationError::forField($field, "$field must be an ISO date, e.g. 1990-01-31");
        }

        return $clean;
    }

    /** Optional free-text name, max 256 characters. */
    public static function name(string $value, string $field = 'name'): string
    {
        $clean = trim($value);
        if ($clean === '' || mb_strlen($clean) > 256) {
            throw ValidationError::forField($field, "$field must be 1-256 characters");
        }

        return $clean;
    }

    private static function match(string $value, string $pattern, string $field, string $hint): string
    {
        $clean = strtoupper(trim($value));
        if (preg_match($pattern, $clean) !== 1) {
            throw ValidationError::forField($field, "$field $hint");
        }

        return $clean;
    }
}
