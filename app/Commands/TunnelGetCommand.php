<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use LaravelZero\Framework\Commands\Command;

class TunnelGetCommand extends Command
{
    use InteractsWithCloudflare;

    protected $signature = 'tunnel:get
        {id : Tunnel ID}
        {--json : Output as JSON}';

    protected $description = 'Get details for a Cloudflare tunnel';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $tunnelId = (string) $this->argument('id');

        $response = $connector->tunnels()->get($tunnelId);

        if (! $response->successful()) {
            $this->error('Failed to get tunnel: '.$response->body());

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
}
