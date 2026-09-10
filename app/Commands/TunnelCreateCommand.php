<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use LaravelZero\Framework\Commands\Command;

class TunnelCreateCommand extends Command
{
    use InteractsWithCloudflare;

    protected $signature = 'tunnel:create
        {name : Name of the tunnel}
        {--json : Output as JSON}';

    protected $description = 'Create a new Cloudflare tunnel';

    public function handle(): int
    {
        $connector = $this->getConnector();
        $name = (string) $this->argument('name');

        $this->info("Creating tunnel: {$name}...");

        $response = $connector->tunnels()->create($name);

        if (! $response->successful()) {
            $this->error('Failed to create tunnel: '.$response->body());

            return self::FAILURE;
        }

        $tunnel = $response->json('result') ?? [];

        if ($this->option('json')) {
            $this->line(json_encode($tunnel, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Tunnel created successfully!');
        $this->displayTunnelCredentials($tunnel);
        $this->displayCloudflaredRunSteps($tunnel, 'http://localhost:8000');

        return self::SUCCESS;
    }
}
