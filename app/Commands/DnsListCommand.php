<?php

declare(strict_types=1);

namespace App\Commands;

use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class DnsListCommand extends Command
{
    protected $signature = 'dns:list
        {zone : Zone ID or domain name}
        {--type= : Filter by record type (A, CNAME, TXT, etc)}
        {--name= : Filter by record name}
        {--json : Output as JSON}';

    protected $description = 'List DNS records for a zone';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $zone = $this->argument('zone');

        // If zone looks like a domain, resolve it to ID
        if (! preg_match('/^[a-f0-9]{32}$/', $zone)) {
            $zoneId = $this->resolveZoneId($connector, $zone);
            if (! $zoneId) {
                $this->error("Zone not found: {$zone}");
                return self::FAILURE;
            }
            $zone = $zoneId;
        }

        $response = $connector->dns($zone)->list(
            $this->option('type'),
            $this->option('name')
        );

        if (! $response->successful()) {
            $this->error('Failed to list DNS records: ' . $response->body());
            return self::FAILURE;
        }

        $records = $response->json('result', []);

        if ($this->option('json')) {
            $this->line(json_encode($records, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        if (empty($records)) {
            $this->info('No DNS records found.');
            return self::SUCCESS;
        }

        $this->table(
            ['Type', 'Name', 'Content', 'Proxied', 'TTL'],
            collect($records)->map(fn ($r) => [
                $r['type'],
                $this->truncate($r['name'], 40),
                $this->truncate($r['content'], 40),
                $r['proxied'] ? 'Yes' : 'No',
                $r['ttl'] === 1 ? 'Auto' : $r['ttl'],
            ])->toArray()
        );

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

    protected function truncate(string $value, int $length): string
    {
        return strlen($value) > $length
            ? substr($value, 0, $length - 3) . '...'
            : $value;
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
