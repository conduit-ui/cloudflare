<?php

declare(strict_types=1);

namespace App\Commands;

use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class DnsUpdateCommand extends Command
{
    protected $signature = 'dns:update
        {zone : Zone ID or domain name}
        {id : DNS record ID to update}
        {type : Record type (A, AAAA, CNAME, TXT, MX, etc)}
        {name : Record name}
        {content : Record content}
        {--proxied : Enable Cloudflare proxy}
        {--ttl=1 : TTL in seconds (1 = auto)}
        {--json : Output as JSON}';

    protected $description = 'Update a DNS record';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $zone = (string) $this->argument('zone');

        // If zone looks like a domain, resolve it to ID
        if (! preg_match('/^[a-f0-9]{32}$/', $zone)) {
            $zoneId = $this->resolveZoneId($connector, $zone);
            if (! $zoneId) {
                $this->error("Zone not found: {$zone}");

                return self::FAILURE;
            }
            $zone = $zoneId;
        }

        $response = $connector->dns($zone)->update(
            (string) $this->argument('id'),
            (string) $this->argument('type'),
            (string) $this->argument('name'),
            (string) $this->argument('content'),
            (bool) $this->option('proxied'),
            (int) $this->option('ttl')
        );

        if (! $response->successful()) {
            $this->error('Failed to update DNS record: '.$response->body());

            return self::FAILURE;
        }

        $record = $response->json('result');

        if ($this->option('json')) {
            $this->line(json_encode($record, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('DNS record updated successfully!');
        $this->table(['Field', 'Value'], [
            ['ID', $record['id']],
            ['Type', $record['type']],
            ['Name', $record['name']],
            ['Content', $record['content']],
            ['Proxied', $record['proxied'] ? 'Yes' : 'No'],
            ['TTL', $record['ttl'] === 1 ? 'Auto' : $record['ttl']],
        ]);

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
