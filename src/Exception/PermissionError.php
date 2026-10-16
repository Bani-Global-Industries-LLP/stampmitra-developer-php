<?php

declare(strict_types=1);

namespace StampMitra\Exception;

/** HTTP 403: the key lacks access (e.g. IP allowlist for passport / driving licence). The server message is surfaced verbatim. */
class PermissionError extends StampMitraError
{
}
