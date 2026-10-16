<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Http\Transport;

final class Payments
{
    public readonly PaymentIntents $intents;

    public function __construct(Transport $transport, bool $validateInput = true)
    {
        $this->intents = new PaymentIntents($transport, $validateInput);
    }
}
