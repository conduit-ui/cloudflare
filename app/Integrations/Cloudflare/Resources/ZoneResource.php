<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Resources;

use App\Integrations\Cloudflare\CloudflareConnector;
use App\Integrations\Cloudflare\Requests\Zones\ListZones;
use Saloon\Http\Response;

class ZoneResource
{
    public function __construct(
        protected CloudflareConnector $connector,
    ) {}

    public function list(?string $name = null): Response
    {
        return $this->connector->send(new ListZones($name));
    }
}
