<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Requests\Dns;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateDnsRecord extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected string $zoneId,
        protected string $recordId,
        protected string $type,
        protected string $name,
        protected string $content,
        protected bool $proxied = false,
        protected int $ttl = 1,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/zones/{$this->zoneId}/dns_records/{$this->recordId}";
    }

    protected function defaultBody(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'content' => $this->content,
            'proxied' => $this->proxied,
            'ttl' => $this->ttl,
        ];
    }
}
