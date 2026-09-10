<?php

declare(strict_types=1);

namespace App\Commands;

use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class TunnelGetCommand extends Command
{
    protected $signature = 'tunnel:get
        {id : Tunnel ID}
        {--json : Output as JSON}';

    protected $description = 'Get details for a Cloudflare tunnel';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $tunnelId = $this->argument('id');

        $response = $connector->tunnels()->get($tunnelId);

        if (! $response->successful()) {
            $this->error('Failed to get tunnel: ' . $response->body());

            return self::FAILURE;
        }

        $tunnel = $response->json('result');

        if ($this->option('json')) {
            $this->line(json_encode($tunnel, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->table(['Field', 'Value'], [
            ['ID', $tunnel['id']],
            ['Name', $tunnel['name']],
            ['Status', $tunnel['status'] ?? 'unknown'],
            ['Created', $tunnel['created_at'] ?? ''],
            ['Connections', count($tunnel['connections'] ?? [])],
        ]);

        return self::SUCCESS;
    }

    protected function getConnector(): CloudflareConnector
    {
        $token = env('CLOUDFLARE_API_TOKEN');
        $accountId = env('CLOUDFLARE_ACCOUNT_ID');

        if (! $token || ! $accountId) {
            $this->error('CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set');
            exit(1);
        }

        return new CloudflareConnector($token, $accountId);
    }
}
