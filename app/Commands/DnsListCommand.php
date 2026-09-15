<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use App\Commands\Concerns\OutputsJson;
use LaravelZero\Framework\Commands\Command;

class DnsListCommand extends Command
{
    use InteractsWithCloudflare;
    use OutputsJson;

    protected $signature = 'dns:list
        {zone : Zone ID or domain name}
        {--type= : Filter by record type (A, CNAME, TXT, etc)}
        {--name= : Filter by record name}
        {--json : Output as JSON}';

    protected $description = 'List DNS records for a zone';

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

        $response = $connector->dns($zoneId)->list(
            $this->option('type'),
            $this->option('name')
        );

        if (! $response->successful()) {
            return $this->jsonFail('Failed to list DNS records: '.$this->formatApiError($response));
        }

        $records = $response->json('result', []);

        if ($this->wantsJson()) {
            return $this->jsonSuccess($records);
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
}
