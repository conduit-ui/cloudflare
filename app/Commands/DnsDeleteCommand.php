<?php

declare(strict_types=1);

namespace App\Commands;

use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class DnsDeleteCommand extends Command
{
    protected $signature = 'dns:delete
        {zone : Zone ID or domain name}
        {id : DNS record ID to delete}
        {--force : Skip confirmation}
        {--json : Output as JSON}';

    protected $description = 'Delete a DNS record';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $zone = (string) $this->argument('zone');
        $recordId = (string) $this->argument('id');

        // If zone looks like a domain, resolve it to ID
        if (! preg_match('/^[a-f0-9]{32}$/', $zone)) {
            $zoneId = $this->resolveZoneId($connector, $zone);
            if (! $zoneId) {
                $this->error("Zone not found: {$zone}");

                return self::FAILURE;
            }
            $zone = $zoneId;
        }

        if (! $this->option('force')) {
            if (! $this->confirm("Delete DNS record {$recordId}?")) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        $response = $connector->dns($zone)->delete($recordId);

        if (! $response->successful()) {
            $this->error('Failed to delete DNS record: '.$response->body());

            return self::FAILURE;
        }

        $result = $response->json('result');

        if ($this->option('json')) {
            $this->line(json_encode($result ?? ['id' => $recordId], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('DNS record deleted successfully.');

        return self::SUCCESS;
    }

    protected function resolveZoneId(CloudflareConnector $connector, string $name): ?string
    {
        $response = $connector->zones()->list($name);
        if ($response->successful()) {
            $zones = $response->json('result', []);

            return $zones[0]['id'] ?? null;
        }

        return null;
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
