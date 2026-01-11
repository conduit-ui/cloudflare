<?php

declare(strict_types=1);

namespace App\Commands;

use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class TunnelCreateCommand extends Command
{
    protected $signature = 'tunnel:create
        {name : Name of the tunnel}
        {--json : Output as JSON}';

    protected $description = 'Create a new Cloudflare tunnel';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $name = $this->argument('name');

        $this->info("Creating tunnel: {$name}...");

        $response = $connector->tunnels()->create($name);

        if (! $response->successful()) {
            $this->error('Failed to create tunnel: ' . $response->body());
            return self::FAILURE;
        }

        $tunnel = $response->json('result');

        if ($this->option('json')) {
            $this->line(json_encode($tunnel, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Tunnel created successfully!');
        $this->table(['Field', 'Value'], [
            ['ID', $tunnel['id']],
            ['Name', $tunnel['name']],
            ['Status', $tunnel['status'] ?? 'inactive'],
            ['Created', $tunnel['created_at'] ?? 'now'],
        ]);

        $this->newLine();
        $this->comment('Next steps:');
        $this->line('  1. Configure ingress rules in ~/.cloudflared/config.yml');
        $this->line('  2. Run: cloudflared tunnel route dns ' . $name . ' <hostname>');
        $this->line('  3. Start tunnel: cloudflared tunnel run ' . $name);

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
