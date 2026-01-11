<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class CloudflareConnector extends Connector
{
    use AcceptsJson;

    public function __construct(
        protected string $apiToken,
        protected ?string $accountId = null,
    ) {}

    public function resolveBaseUrl(): string
    {
        return 'https://api.cloudflare.com/client/v4';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
        ];
    }

    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    public function tunnels(): Resources\TunnelResource
    {
        return new Resources\TunnelResource($this);
    }

    public function dns(string $zoneId): Resources\DnsResource
    {
        return new Resources\DnsResource($this, $zoneId);
    }

    public function zones(): Resources\ZoneResource
    {
        return new Resources\ZoneResource($this);
    }
}
