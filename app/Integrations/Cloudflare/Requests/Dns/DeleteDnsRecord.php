<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Requests\Dns;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteDnsRecord extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected string $zoneId,
        protected string $recordId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/zones/{$this->zoneId}/dns_records/{$this->recordId}";
    }
}
