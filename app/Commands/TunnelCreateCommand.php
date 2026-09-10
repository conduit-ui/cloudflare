<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use App\Commands\Concerns\OutputsJson;
use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class TunnelCreateCommand extends Command
{
    use InteractsWithCloudflare {
        getConnector as private unusedTraitGetConnector;
    }
    use OutputsJson;

    protected $signature = 'tunnel:create
        {name : Name of the tunnel}
        {--json : Output as JSON}';

    protected $description = 'Create a new Cloudflare tunnel';

    public function handle(): int
    {
        $connector = $this->getConnector();
        if ($connector === null) {
            return self::FAILURE;
        }

        $name = $this->argument('name');

        if (! $this->wantsJson()) {
            $this->info("Creating tunnel: {$name}...");
        }

        $response = $connector->tunnels()->create($name);

        if (! $response->successful()) {
            return $this->jsonFail('Failed to create tunnel: '.$response->body());
        }

        $tunnel = $response->json('result') ?? [];

        if ($this->wantsJson()) {
            return $this->jsonSuccess($tunnel);
        }

        $this->newLine();
        $this->info('Tunnel created successfully!');
        $this->displayTunnelCredentials($tunnel);
        $this->displayCloudflaredRunSteps($tunnel, 'http://localhost:8000');

        return self::SUCCESS;
    }

    protected function getConnector(): ?CloudflareConnector
    {
        $token = env('CLOUDFLARE_API_TOKEN');
        $accountId = env('CLOUDFLARE_ACCOUNT_ID');

        if (! $token || ! $accountId) {
            $this->jsonFail('CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set');

            return null;
        }

        return new CloudflareConnector($token, $accountId);
    }
}
