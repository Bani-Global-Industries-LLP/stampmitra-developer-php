<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Http\Transport;

final class Webhooks
{
    public readonly WebhookEvents $events;

    public function __construct(Transport $transport, bool $validateInput = true)
    {
        $this->events = new WebhookEvents($transport, $validateInput);
    }
}
