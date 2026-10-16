<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Http\RequestOptions;
use StampMitra\Model\UsageSummary;

final class Usage extends AbstractResource
{
    /** @param RequestOptions|array<string, mixed>|null $options */
    public function summary(RequestOptions|array|null $options = null): UsageSummary
    {
        return $this->call(UsageSummary::class, 'GET', '/v1/usage/summary', null, [], $options);
    }
}
