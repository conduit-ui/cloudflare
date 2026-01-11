<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Requests\Tunnels;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListTunnels extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected ?string $accountId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/accounts/{$this->accountId}/cfd_tunnel";
    }
}
