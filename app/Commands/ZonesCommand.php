<?php

declare(strict_types=1);

namespace App\Commands;

use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class ZonesCommand extends Command
{
    protected $signature = 'zones
        {--name= : Filter by zone name}
        {--json : Output as JSON}';

    protected $description = 'List Cloudflare zones';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $response = $connector->zones()->list($this->option('name'));

        if (! $response->successful()) {
            $this->error('Failed to list zones: ' . $response->body());
            return self::FAILURE;
        }

        $zones = $response->json('result', []);

        if ($this->option('json')) {
            $this->line(json_encode($zones, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        if (empty($zones)) {
            $this->info('No zones found.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Status', 'Plan'],
            collect($zones)->map(fn ($z) => [
                $z['id'],
                $z['name'],
                $z['status'],
                $z['plan']['name'] ?? 'unknown',
            ])->toArray()
        );

        return self::SUCCESS;
    }

    protected function getConnector(): CloudflareConnector
    {
        $token = env('CLOUDFLARE_API_TOKEN');
        $accountId = env('CLOUDFLARE_ACCOUNT_ID');

        if (! $token) {
            $this->error('CLOUDFLARE_API_TOKEN not set');
            exit(1);
        }

        return new CloudflareConnector($token, $accountId);
    }
}
