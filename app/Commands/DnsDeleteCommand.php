<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use App\Commands\Concerns\OutputsJson;
use LaravelZero\Framework\Commands\Command;

class DnsDeleteCommand extends Command
{
    use InteractsWithCloudflare;
    use OutputsJson;

    protected $signature = 'dns:delete
        {zone : Zone ID or domain name}
        {id : DNS record ID to delete}
        {--force : Skip confirmation}
        {--json : Output as JSON}';

    protected $description = 'Delete a DNS record';

    public function handle(): int
    {
        $connector = $this->getConnector();
        if ($connector === null) {
            return self::FAILURE;
        }

        $zone = (string) $this->argument('zone');
        $recordId = (string) $this->argument('id');

        $zoneId = $this->resolveZoneId($connector, $zone);
        if (! $zoneId) {
            return $this->jsonFail("Zone not found: {$zone}");
        }

        if (! $this->option('force') && ! $this->wantsJson()) {
            if (! $this->confirm("Delete DNS record {$recordId}?")) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        $response = $connector->dns($zoneId)->delete($recordId);

        if (! $response->successful()) {
            return $this->jsonFail('Failed to delete DNS record: '.$this->formatApiError($response));
        }

        $result = $response->json('result');

        if ($this->wantsJson()) {
            return $this->jsonSuccess($result ?? ['id' => $recordId]);
        }

        $this->info('DNS record deleted successfully.');

        return self::SUCCESS;
    }
}
