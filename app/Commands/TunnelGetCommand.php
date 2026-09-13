<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use App\Commands\Concerns\OutputsJson;
use LaravelZero\Framework\Commands\Command;

class TunnelGetCommand extends Command
{
    use InteractsWithCloudflare;
    use OutputsJson;

    protected $signature = 'tunnel:get
        {id : Tunnel ID}
        {--json : Output as JSON}';

    protected $description = 'Get details for a Cloudflare tunnel';

    public function handle(): int
    {
        $connector = $this->getConnector();
        if ($connector === null) {
            return self::FAILURE;
        }

        $tunnelId = (string) $this->argument('id');

        $response = $connector->tunnels()->get($tunnelId);

        if (! $response->successful()) {
            return $this->jsonFail('Failed to get tunnel: '.$this->formatApiError($response));
        }

        $tunnel = $response->json('result');

        if ($this->wantsJson()) {
            return $this->jsonSuccess($tunnel);
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
