<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\OutputsJson;
use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class ZonesCommand extends Command
{
    use OutputsJson;

    protected $signature = 'zones
        {--name= : Filter by zone name}
        {--json : Output as JSON}';

    protected $description = 'List Cloudflare zones';

    public function handle(): int
    {
        $connector = $this->getConnector();
        if ($connector === null) {
            return self::FAILURE;
        }

        $response = $connector->zones()->list($this->option('name'));

        if (! $response->successful()) {
            return $this->jsonFail('Failed to list zones: '.$response->body());
        }

        $zones = $response->json('result', []);

        if ($this->wantsJson()) {
            return $this->jsonSuccess($zones);
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

    protected function getConnector(): ?CloudflareConnector
    {
        $token = env('CLOUDFLARE_API_TOKEN');
        $accountId = env('CLOUDFLARE_ACCOUNT_ID');

        if (! $token) {
            $this->jsonFail('CLOUDFLARE_API_TOKEN not set');

            return null;
        }

        return new CloudflareConnector($token, $accountId);
    }
}
