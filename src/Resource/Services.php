<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Http\RequestOptions;
use StampMitra\Model\Service;
use StampMitra\Model\ServiceList;

final class Services extends AbstractResource
{
    /** GET /v1/services
     * @param RequestOptions|array<string, mixed>|null $options */
    public function list(RequestOptions|array|null $options = null): ServiceList
    {
        return $this->call(ServiceList::class, 'GET', '/v1/services', null, [], $options);
    }

    /** GET /v1/services/{serviceCode}
     * @param RequestOptions|array<string, mixed>|null $options */
    public function get(string $serviceCode, RequestOptions|array|null $options = null): Service
    {
        return $this->call(Service::class, 'GET', '/v1/services/' . $this->segment($serviceCode, 'serviceCode'), null, [], $options);
    }
}
