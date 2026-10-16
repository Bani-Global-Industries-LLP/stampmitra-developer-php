<?php

declare(strict_types=1);

namespace StampMitra\Support;

/**
 * Masks credentials and personal data before anything reaches a logger.
 */
final class Redactor
{
    private const SECRET_HEADERS = ['authorization', 'x-api-key', 'idempotency-key-secret', 'cookie', 'set-cookie'];

    /** Keys whose values are identifiers: the last four characters stay visible. */
    private const IDENTIFIER_KEYS = [
        'pan', 'gstin', 'accountnumber', 'ifsc', 'cin', 'udyamnumber', 'vehiclenumber', 'regno',
        'dlnumber', 'filenumber', 'voterid', 'phone', 'din', 'chassis', 'engine', 'micr', 'utr', 'email',
    ];

    /** Keys whose values are personal data and are removed entirely. */
    private const PERSONAL_KEYS = [
        'name', 'businessname', 'registeredname', 'fathername', 'ownername', 'ownerfathername',
        'nameatbank', 'fatherorhusbandname', 'companyname', 'tradename', 'legalname', 'dob',
        'address', 'principalplaceofbusiness', 'enterprisename',
    ];

    /**
     * @param array<string, string|list<string>> $headers
     * @return array<string, string>
     */
    public static function headers(array $headers): array
    {
        $out = [];
        foreach ($headers as $name => $value) {
            $out[$name] = in_array(strtolower($name), self::SECRET_HEADERS, true)
                ? '[REDACTED]'
                : (is_array($value) ? implode(', ', $value) : $value);
        }

        return $out;
    }

    public static function mask(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }
        $out = [];
        foreach ($data as $key => $value) {
            $lower = is_string($key) ? strtolower($key) : '';
            if (is_array($value)) {
                $out[$key] = self::mask($value);
            } elseif (in_array($lower, self::PERSONAL_KEYS, true)) {
                $out[$key] = '[REDACTED]';
            } elseif (in_array($lower, self::IDENTIFIER_KEYS, true) && is_scalar($value)) {
                $s = (string) $value;
                $out[$key] = strlen($s) > 4 ? str_repeat('*', strlen($s) - 4) . substr($s, -4) : '****';
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /** Mask a JSON body; non-JSON text is replaced with a length marker. */
    public static function body(string $body): string
    {
        if ($body === '') {
            return '';
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return '[non-JSON body, ' . strlen($body) . ' bytes]';
        }

        return (string) json_encode(self::mask($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
