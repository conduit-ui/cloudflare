<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Requests\Tunnels;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateTunnel extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected ?string $accountId,
        protected string $name,
        protected string $configSrc = 'cloudflare',
    ) {}

    public function resolveEndpoint(): string
    {
        return "/accounts/{$this->accountId}/cfd_tunnel";
    }

    protected function defaultBody(): array
    {
        return [
            'name' => $this->name,
            'config_src' => $this->configSrc,
            'tunnel_secret' => base64_encode(random_bytes(32)),
        ];
    }
}
