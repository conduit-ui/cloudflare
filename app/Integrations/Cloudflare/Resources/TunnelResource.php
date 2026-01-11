<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Resources;

use App\Integrations\Cloudflare\CloudflareConnector;
use App\Integrations\Cloudflare\Requests\Tunnels\CreateTunnel;
use App\Integrations\Cloudflare\Requests\Tunnels\DeleteTunnel;
use App\Integrations\Cloudflare\Requests\Tunnels\GetTunnel;
use App\Integrations\Cloudflare\Requests\Tunnels\ListTunnels;
use Saloon\Http\Response;

class TunnelResource
{
    public function __construct(
        protected CloudflareConnector $connector,
    ) {}

    public function list(): Response
    {
        return $this->connector->send(new ListTunnels(
            $this->connector->getAccountId()
        ));
    }

    public function get(string $tunnelId): Response
    {
        return $this->connector->send(new GetTunnel(
            $this->connector->getAccountId(),
            $tunnelId
        ));
    }

    public function create(string $name, string $configSrc = 'cloudflare'): Response
    {
        return $this->connector->send(new CreateTunnel(
            $this->connector->getAccountId(),
            $name,
            $configSrc
        ));
    }

    public function delete(string $tunnelId): Response
    {
        return $this->connector->send(new DeleteTunnel(
            $this->connector->getAccountId(),
            $tunnelId
        ));
    }
}
