<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use LaravelZero\Framework\Commands\Command;

class TunnelListCommand extends Command
{
    use InteractsWithCloudflare;

    protected $signature = 'tunnel:list
        {--json : Output as JSON}';

    protected $description = 'List all Cloudflare tunnels';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $response = $connector->tunnels()->list();

        if (! $response->successful()) {
            $this->error('Failed to list tunnels: '.$response->body());

            return self::FAILURE;
        }

        $tunnels = $response->json('result', []);

        if ($this->option('json')) {
            $this->line(json_encode($tunnels, JSON_PRETTY_PRINT));

            return self::SUCCESS;
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
