<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use StampMitra\Exception\RateLimitError;
use StampMitra\Exception\StampMitraError;
use StampMitra\StampMitra;

// Reads STAMPMITRA_API_KEY (sk_test_... for the sandbox).
$client = new StampMitra();

try {
    $result = $client->verify->pan('ABCDE1234F', 'Rahul Kumar');
    printf(
        "valid=%s name=%s match=%s reference=%s\n",
        var_export($result->valid, true),
        $result->registeredName ?? '-',
        $result->nameMatchResult ?? '-',
        $result->referenceId ?? '-',
    );
} catch (RateLimitError $e) {
    fwrite(STDERR, "Rate limited, retry in {$e->getRetryAfter()}s (request {$e->getRequestId()})\n");
    exit(1);
} catch (StampMitraError $e) {
    fwrite(STDERR, sprintf("API error %s: %s (request %s)\n", (string) $e->getStatus(), $e->getMessage(), (string) $e->getRequestId()));
    exit(1);
}
