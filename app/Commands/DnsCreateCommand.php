<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\OutputsJson;
use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class DnsCreateCommand extends Command
{
    use OutputsJson;

    protected $signature = 'dns:create
        {zone : Zone ID or domain name}
        {type : Record type (A, AAAA, CNAME, TXT, MX, etc)}
        {name : Record name}
        {content : Record content}
        {--proxied : Enable Cloudflare proxy}
        {--ttl=1 : TTL in seconds (1 = auto)}
        {--json : Output as JSON}';

    protected $description = 'Create a DNS record';

    public function handle(): int
    {
        $connector = $this->getConnector();
        if ($connector === null) {
            return self::FAILURE;
        }

        $zone = $this->argument('zone');

        if (! preg_match('/^[a-f0-9]{32}$/', $zone)) {
            $zoneId = $this->resolveZoneId($connector, $zone);
            if (! $zoneId) {
                return $this->jsonFail("Zone not found: {$zone}");
            }
            $zone = $zoneId;
        }

        $response = $connector->dns($zone)->create(
            $this->argument('type'),
            $this->argument('name'),
            $this->argument('content'),
            (bool) $this->option('proxied'),
            (int) $this->option('ttl')
        );

        if (! $response->successful()) {
            return $this->jsonFail('Failed to create DNS record: '.$response->body());
        }

        $record = $response->json('result');

        if ($this->wantsJson()) {
            return $this->jsonSuccess($record);
        }

        $this->info('DNS record created successfully!');
        $this->table(['Field', 'Value'], [
            ['ID', $record['id']],
            ['Type', $record['type']],
            ['Name', $record['name']],
            ['Content', $record['content']],
            ['Proxied', $record['proxied'] ? 'Yes' : 'No'],
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
