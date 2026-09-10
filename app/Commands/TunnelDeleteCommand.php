<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\OutputsJson;
use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class TunnelDeleteCommand extends Command
{
    use OutputsJson;

    protected $signature = 'tunnel:delete
        {id : Tunnel ID to delete}
        {--force : Skip confirmation}
        {--json : Output as JSON}';

    protected $description = 'Delete a Cloudflare tunnel';

    public function handle(): int
    {
        $connector = $this->getConnector();
        if ($connector === null) {
            return self::FAILURE;
        }

        $tunnelId = $this->argument('id');

        if (! $this->option('force')) {
            if (! $this->confirm("Delete tunnel {$tunnelId}?")) {
                if ($this->wantsJson()) {
                    return $this->jsonSuccess(['deleted' => false, 'cancelled' => true]);
                }

                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        $response = $connector->tunnels()->delete($tunnelId);

        if (! $response->successful()) {
            return $this->jsonFail('Failed to delete tunnel: '.$response->body());
        }

        if ($this->wantsJson()) {
            return $this->jsonSuccess(['deleted' => true, 'id' => $tunnelId]);
        }

        $this->info('Tunnel deleted successfully.');

        return self::SUCCESS;
    }

    protected function getConnector(): ?CloudflareConnector
    {
        $token = env('CLOUDFLARE_API_TOKEN');
        $accountId = env('CLOUDFLARE_ACCOUNT_ID');

        if (! $token || ! $accountId) {
            $this->jsonFail('CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set');

            return null;
        }

        return new CloudflareConnector($token, $accountId);
    }
}
