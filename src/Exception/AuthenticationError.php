<?php

declare(strict_types=1);

namespace StampMitra\Exception;

/** HTTP 401: missing, malformed or revoked API key. */
class AuthenticationError extends StampMitraError
{
}
