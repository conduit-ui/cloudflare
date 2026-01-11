<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Requests\Tunnels;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteTunnel extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected ?string $accountId,
        protected string $tunnelId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/accounts/{$this->accountId}/cfd_tunnel/{$this->tunnelId}";
    }
}
