<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use App\Commands\Concerns\OutputsJson;
use LaravelZero\Framework\Commands\Command;

class DnsUpdateCommand extends Command
{
    use InteractsWithCloudflare;
    use OutputsJson;

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
        if ($connector === null) {
            return self::FAILURE;
        }

        $zone = (string) $this->argument('zone');
        $zoneId = $this->resolveZoneId($connector, $zone);
        if (! $zoneId) {
            return $this->jsonFail("Zone not found: {$zone}");
        }

        $response = $connector->dns($zoneId)->update(
            (string) $this->argument('id'),
            (string) $this->argument('type'),
            (string) $this->argument('name'),
            (string) $this->argument('content'),
            (bool) $this->option('proxied'),
            (int) $this->option('ttl')
        );

        if (! $response->successful()) {
            return $this->jsonFail('Failed to update DNS record: '.$this->formatApiError($response));
        }

        $record = $response->json('result');

        if ($this->wantsJson()) {
            return $this->jsonSuccess($record);
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
}
