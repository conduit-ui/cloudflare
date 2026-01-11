<?php

declare(strict_types=1);

namespace App\Commands;

use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class TunnelDeleteCommand extends Command
{
    protected $signature = 'tunnel:delete
        {id : Tunnel ID to delete}
        {--force : Skip confirmation}';

    protected $description = 'Delete a Cloudflare tunnel';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $tunnelId = $this->argument('id');

        if (! $this->option('force')) {
            if (! $this->confirm("Delete tunnel {$tunnelId}?")) {
                $this->info('Cancelled.');
                return self::SUCCESS;
            }
        }

        $response = $connector->tunnels()->delete($tunnelId);

        if (! $response->successful()) {
            $this->error('Failed to delete tunnel: ' . $response->body());
            return self::FAILURE;
        }

        $this->info('Tunnel deleted successfully.');
        return self::SUCCESS;
    }

    protected function getConnector(): CloudflareConnector
    {
        $token = env('CLOUDFLARE_API_TOKEN');
        $accountId = env('CLOUDFLARE_ACCOUNT_ID');

        if (! $token || ! $accountId) {
            $this->error('CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set');
            exit(1);
        }

        return new CloudflareConnector($token, $accountId);
    }
}
