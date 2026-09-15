<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use App\Commands\Concerns\OutputsJson;
use LaravelZero\Framework\Commands\Command;

class TunnelListCommand extends Command
{
    use InteractsWithCloudflare;
    use OutputsJson;

    protected $signature = 'tunnel:list
        {--json : Output as JSON}';

    protected $description = 'List all Cloudflare tunnels';

    public function handle(): int
    {
        $connector = $this->getConnector();
        if ($connector === null) {
            return self::FAILURE;
        }

        $response = $connector->tunnels()->list();

        if (! $response->successful()) {
            return $this->jsonFail('Failed to list tunnels: '.$this->formatApiError($response));
        }

        $tunnels = $response->json('result', []);

        if ($this->wantsJson()) {
            return $this->jsonSuccess($tunnels);
        }

        if (empty($tunnels)) {
            $this->info('No tunnels found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Status', 'Created', 'Connections'],
            collect($tunnels)->map(fn ($t) => [
                substr($t['id'], 0, 8).'...',
                $t['name'],
                $t['status'] ?? 'unknown',
                substr($t['created_at'] ?? '', 0, 10),
                count($t['connections'] ?? []),
            ])->toArray()
        );

        return self::SUCCESS;
    }
}
