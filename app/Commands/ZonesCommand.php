<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use App\Commands\Concerns\OutputsJson;
use LaravelZero\Framework\Commands\Command;

class ZonesCommand extends Command
{
    use InteractsWithCloudflare;
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
            return $this->jsonFail('Failed to list zones: '.$this->formatApiError($response));
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
}
