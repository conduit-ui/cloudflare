<?php

declare(strict_types=1);

namespace App\Commands;

use App\Integrations\Cloudflare\CloudflareConnector;
use LaravelZero\Framework\Commands\Command;

class SetupCommand extends Command
{
    protected $signature = 'setup
        {--token= : Cloudflare API token}
        {--account-id= : Cloudflare account ID}
        {--non-interactive : Fail instead of prompting for missing values}
        {--json : Output as JSON}';

    protected $description = 'Configure Cloudflare API credentials in .env';

    public function handle(): int
    {
        $token = $this->option('token') ?: null;
        $accountId = $this->option('account-id') ?: null;
        $nonInteractive = (bool) $this->option('non-interactive');

        if (! $token) {
            if ($nonInteractive) {
                return $this->failSetup('CLOUDFLARE_API_TOKEN is required. Pass --token= or run without --non-interactive.');
            }

            $token = $this->secret('Cloudflare API token');
        }

        if (! $accountId) {
            if ($nonInteractive) {
                return $this->failSetup('CLOUDFLARE_ACCOUNT_ID is required. Pass --account-id= or run without --non-interactive.');
            }

            $accountId = $this->ask('Cloudflare account ID');
        }

        $token = is_string($token) ? trim($token) : '';
        $accountId = is_string($accountId) ? trim($accountId) : '';

        if ($token === '' || $accountId === '') {
            return $this->failSetup('Both API token and account ID are required.');
        }

        $connector = new CloudflareConnector($token, $accountId);
        $response = $connector->user()->verifyToken();

        if (! $response->successful() || $response->json('success') !== true) {
            $message = $response->json('errors.0.message')
                ?? ('Token verification failed: '.$response->body());

            return $this->failSetup($message);
        }

        $this->writeEnv([
            'CLOUDFLARE_API_TOKEN' => $token,
            'CLOUDFLARE_ACCOUNT_ID' => $accountId,
        ]);

        $payload = [
            'success' => true,
            'message' => 'Cloudflare credentials saved to .env',
            'account_id' => $accountId,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info($payload['message']);
        $this->line('Account ID: '.$accountId);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $values
     */
    protected function writeEnv(array $values): void
    {
        $path = base_path('.env');

        $contents = file_exists($path) ? file_get_contents($path) : '';
        if ($contents === false) {
            $contents = '';
        }

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->escapeEnvValue($value);

            if (preg_match("/^{$key}=.*/m", $contents)) {
                $contents = preg_replace("/^{$key}=.*/m", $line, $contents) ?? $contents;
            } else {
                $contents = rtrim($contents);
                $contents .= ($contents === '' ? '' : PHP_EOL).$line.PHP_EOL;
            }
        }

        file_put_contents($path, $contents);
    }

    protected function escapeEnvValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|"|\'|=/', $value)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }

    protected function failSetup(string $message): int
    {
        if ($this->option('json')) {
            $this->line(json_encode([
                'success' => false,
                'message' => $message,
            ], JSON_PRETTY_PRINT));
        } else {
            $this->error($message);
        }

        return self::FAILURE;
    }
}
