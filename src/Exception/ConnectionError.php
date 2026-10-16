<?php

declare(strict_types=1);

namespace StampMitra\Exception;

/** No usable response was received (DNS, connect, TLS or read failure). */
class ConnectionError extends StampMitraError
{
}
