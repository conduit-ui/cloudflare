<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Requests\Tunnels;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateTunnelConfiguration extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    /**
     * @param  array<int, array<string, mixed>>  $ingress
     */
    public function __construct(
        protected ?string $accountId,
        protected string $tunnelId,
        protected array $ingress,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/accounts/{$this->accountId}/cfd_tunnel/{$this->tunnelId}/configurations";
    }

    protected function defaultBody(): array
    {
        return [
            'config' => [
                'ingress' => $this->ingress,
            ],
        ];
    }
}
