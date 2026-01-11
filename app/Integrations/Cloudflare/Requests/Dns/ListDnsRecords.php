<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Requests\Dns;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListDnsRecords extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $zoneId,
        protected ?string $type = null,
        protected ?string $name = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/zones/{$this->zoneId}/dns_records";
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'type' => $this->type,
            'name' => $this->name,
        ]);
    }
}
