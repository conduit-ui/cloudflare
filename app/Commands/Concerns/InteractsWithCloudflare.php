<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Integrations\Cloudflare\CloudflareConnector;
use Saloon\Http\Response;

trait InteractsWithCloudflare
{
    /**
     * Resolve the Cloudflare connector from the environment.
     * Prints the missing-creds error once, returns null, never exits.
     */
    protected function getConnector(): ?CloudflareConnector
    {
        $token = env('CLOUDFLARE_API_TOKEN');
        $accountId = env('CLOUDFLARE_ACCOUNT_ID');

        if (! $token || ! $accountId) {
            $this->reportMissingCloudflareCredentials();

            return null;
        }

        return new CloudflareConnector($token, $accountId);
    }

    protected function reportMissingCloudflareCredentials(): void
    {
        $message = 'CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set';

        if (in_array('jsonFail', get_class_methods($this), true)) {
            $this->jsonFail($message);

            return;
        }

        $this->error($message);
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

    /**
     * Resolve a zone ID or domain name to a zone ID.
     */
    protected function resolveZoneId(CloudflareConnector $connector, string $zone): ?string
    {
        if (preg_match('/^[a-f0-9]{32}$/', $zone) === 1) {
            return $zone;
        }

        $response = $connector->zones()->list($zone);

        if ($response->successful()) {
            $zones = $response->json('result', []);

            return $zones[0]['id'] ?? null;
        }

        return null;
    }

    protected function truncate(string $value, int $length): string
    {
        return strlen($value) > $length
            ? substr($value, 0, $length - 3).'...'
            : $value;
    }

    /**
     * Prefer Cloudflare errors.0.message when present.
     */
    protected function formatApiError(Response $response): string
    {
        $message = $response->json('errors.0.message');

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return $response->body();
    }
}
