<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Integrations\Cloudflare\CloudflareConnector;

trait InteractsWithCloudflare
{
    protected function getConnector(): ?CloudflareConnector
    {
        $token = env('CLOUDFLARE_API_TOKEN');
        $accountId = env('CLOUDFLARE_ACCOUNT_ID');

        if (! $token || ! $accountId) {
            $message = 'CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set';

            if (method_exists($this, 'jsonFail')) {
                $this->jsonFail($message);
            } else {
                $this->error($message);
            }

            return null;
        }

        return new CloudflareConnector($token, $accountId);
    }

    protected function maskSecret(string $value): string
    {
        $length = strlen($value);

        if ($length <= 12) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 6).str_repeat('*', max(8, $length - 10)).substr($value, -4);
    }

    /**
     * Summary table with masked secrets, then full credentials once.
     *
     * @param  array<string, mixed>  $tunnel
     */
    protected function displayTunnelCredentials(array $tunnel): void
    {
        $rows = [
            ['ID', (string) ($tunnel['id'] ?? '')],
            ['Name', (string) ($tunnel['name'] ?? '')],
            ['Status', (string) ($tunnel['status'] ?? 'inactive')],
            ['Created', (string) ($tunnel['created_at'] ?? 'now')],
        ];

        if (! empty($tunnel['token'])) {
            $rows[] = ['Token', $this->maskSecret((string) $tunnel['token'])];
        }

        if (! empty($tunnel['credentials_file']['TunnelSecret'])) {
            $rows[] = ['TunnelSecret', $this->maskSecret((string) $tunnel['credentials_file']['TunnelSecret'])];
        }

        $this->table(['Field', 'Value'], $rows);

        $token = $tunnel['token'] ?? null;
        $credentials = $tunnel['credentials_file'] ?? null;

        if (! $token && empty($credentials)) {
            return;
        }

        $this->newLine();
        $this->warn('Credentials are only returned at create time — store them securely.');

        if ($token) {
            $this->line('Tunnel token (full):');
            $this->line((string) $token);
        }

        if (! empty($credentials)) {
            $this->line('Credentials file (full):');
            $this->line(json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * @param  array<string, mixed>  $tunnel
     */
    protected function displayCloudflaredRunSteps(array $tunnel, string $service, ?string $hostname = null): void
    {
        $tunnelId = $tunnel['id'] ?? '<tunnel-id>';
        $name = $tunnel['name'] ?? '<name>';
        $token = $tunnel['token'] ?? null;

        $this->newLine();
        $this->comment('Expose your local app:');
        $this->line('  1. Install cloudflared: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/');

        if ($token) {
            $this->line('  2. Run: cloudflared tunnel run --token '.$token);
        } else {
            $this->line('  2. Run: cloudflared tunnel run '.$name);
        }

        $this->line("  3. Local service for ingress: {$service}");

        if ($hostname) {
            $this->line("  4. DNS (if not created above): cloudflared tunnel route dns {$name} {$hostname}");
            $this->line("     or CNAME {$hostname} → {$tunnelId}.cfargotunnel.com");
        } else {
            $this->line("  4. Optional DNS: cloudflared tunnel route dns {$name} <hostname>");
        }
    }

    protected function resolveZoneIdForHostname(CloudflareConnector $connector, string $hostname): ?string
    {
        $parts = explode('.', strtolower($hostname));

        while (count($parts) >= 2) {
            $candidate = implode('.', $parts);
            $response = $connector->zones()->list($candidate);

            if ($response->successful()) {
                $zones = $response->json('result', []);

                if (! empty($zones[0]['id'])) {
                    return $zones[0]['id'];
                }
            }

            array_shift($parts);
        }

        return null;
    }
}
