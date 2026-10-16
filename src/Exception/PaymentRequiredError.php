<?php

declare(strict_types=1);

namespace StampMitra\Exception;

/** HTTP 402: insufficient balance or quota for a metered call. */
class PaymentRequiredError extends StampMitraError
{
}
