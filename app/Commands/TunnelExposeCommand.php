<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithCloudflare;
use App\Commands\Concerns\OutputsJson;
use LaravelZero\Framework\Commands\Command;

class TunnelExposeCommand extends Command
{
    use InteractsWithCloudflare;
    use OutputsJson;

    protected $signature = 'tunnel:expose
        {name : Name of the tunnel}
        {hostname? : Public hostname to route (optional)}
        {url=http://localhost:8000 : Local service URL to expose}
        {--json : Output as JSON}';

    protected $description = 'Create a tunnel and print cloudflared steps to expose a local app';

    public function handle(): int
    {
        $connector = $this->getConnector();
        if ($connector === null) {
            return self::FAILURE;
        }

        $name = (string) $this->argument('name');
        $hostname = $this->argument('hostname') ? (string) $this->argument('hostname') : null;
        $url = (string) $this->argument('url');

        if (! $this->wantsJson()) {
            $this->info("Creating tunnel \"{$name}\" to expose {$url}...");
        }

        $response = $connector->tunnels()->create($name);

        if (! $response->successful()) {
            return $this->jsonFail('Failed to create tunnel: '.$response->body());
        }

        $tunnel = $response->json('result') ?? [];
        $tunnelId = (string) ($tunnel['id'] ?? '');
        $token = $tunnel['token'] ?? null;

        $ingressConfigured = false;
        $dnsCreated = false;
        $dnsRecord = null;
        $configuration = null;
        $dnsRouteCommand = $hostname
            ? "cloudflared tunnel route dns {$name} {$hostname}"
            : "cloudflared tunnel route dns {$name} <hostname>";

        if ($hostname && $tunnelId !== '') {
            $ingress = [
                [
                    'hostname' => $hostname,
                    'service' => $url,
                    'originRequest' => (object) [],
                ],
                ['service' => 'http_status:404'],
            ];

            $configResponse = $connector->tunnels()->configure($tunnelId, $ingress);

            if ($configResponse->successful()) {
                $ingressConfigured = true;
                $configuration = $configResponse->json('result');
            }

            $zoneId = $this->resolveZoneIdForHostname($connector, $hostname);

            if ($zoneId) {
                $dnsResponse = $connector->dns($zoneId)->create(
                    'CNAME',
                    $hostname,
                    "{$tunnelId}.cfargotunnel.com",
                    proxied: true,
                );

                if ($dnsResponse->successful()) {
                    $dnsCreated = true;
                    $dnsRecord = $dnsResponse->json('result');
                }
            }
        }

        if ($this->wantsJson()) {
            return $this->jsonSuccess([
                'tunnel' => $tunnel,
                'hostname' => $hostname,
                'url' => $url,
                'ingress_configured' => $ingressConfigured,
                'dns_created' => $dnsCreated,
                'dns_record' => $dnsRecord,
                'configuration' => $configuration,
                'dns_route_command' => $dnsRouteCommand,
            ]);
        }

        $this->newLine();
        $this->info('Tunnel ready to expose your app!');
        $this->displayTunnelCredentials($tunnel);

        if ($hostname) {
            $this->newLine();
            $this->comment('Hostname routing:');

            if ($ingressConfigured) {
                $this->line("  Ingress configured: {$hostname} → {$url}");
            } else {
                $this->warn('  Could not set remote ingress via API; configure in the dashboard or config.yml.');
            }

            if ($dnsCreated) {
                $this->line('  DNS CNAME created: '.(($dnsRecord['name'] ?? null) ?: $hostname));
            } else {
                $this->warn('  DNS not created via API — run:');
                $this->line("       {$dnsRouteCommand}");
            }
        }

        $this->displayCloudflaredRunSteps($tunnel, $url, $hostname);

        if ($token) {
            $this->newLine();
            $this->line('Quick start: cloudflared tunnel run --token '.$token);
        }

        return self::SUCCESS;
    }
}
