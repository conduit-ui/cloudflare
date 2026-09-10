<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\User\VerifyToken;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function () {
    MockClient::destroyGlobal();
});

it('fails non-interactively when token is missing', function () {
    $this->artisan('setup', [
        '--account-id' => 'account-123',
        '--non-interactive' => true,
    ])
        ->expectsOutputToContain('CLOUDFLARE_API_TOKEN is required')
        ->assertExitCode(1);
});

it('fails non-interactively when account id is missing', function () {
    $this->artisan('setup', [
        '--token' => 'cf_test_token',
        '--non-interactive' => true,
    ])
        ->expectsOutputToContain('CLOUDFLARE_ACCOUNT_ID is required')
        ->assertExitCode(1);
});

it('fails non-interactively when token verification fails', function () {
    MockClient::global([
        VerifyToken::class => MockResponse::make([
            'success' => false,
            'errors' => [
                ['message' => 'Invalid API Token'],
            ],
        ], 401),
    ]);

    $this->artisan('setup', [
        '--token' => 'bad-token',
        '--account-id' => 'account-123',
        '--non-interactive' => true,
    ])
        ->expectsOutputToContain('Invalid API Token')
        ->assertExitCode(1);
});

it('returns json failure payload when verification fails', function () {
    MockClient::global([
        VerifyToken::class => MockResponse::make([
            'success' => false,
            'errors' => [
                ['message' => 'Invalid API Token'],
            ],
        ], 401),
    ]);

    $this->artisan('setup', [
        '--token' => 'bad-token',
        '--account-id' => 'account-123',
        '--non-interactive' => true,
        '--json' => true,
    ])
        ->expectsOutputToContain('Invalid API Token')
        ->assertExitCode(1);
});

it('saves credentials after a successful token verify', function () {
    $path = base_path('.env');
    $original = file_exists($path) ? file_get_contents($path) : null;

    MockClient::global([
        VerifyToken::class => MockResponse::make([
            'success' => true,
            'result' => ['status' => 'active'],
        ], 200),
    ]);

    try {
        $status = Artisan::call('setup', [
            '--token' => 'cf_good_token',
            '--account-id' => 'account-123',
            '--non-interactive' => true,
            '--json' => true,
        ]);
        $output = Artisan::output();

        expect($status)->toBe(0)
            ->and($output)->toContain('"success": true')
            ->and($output)->toContain('Cloudflare credentials saved to .env');

        expect(file_get_contents($path))
            ->toContain('CLOUDFLARE_API_TOKEN=cf_good_token')
            ->toContain('CLOUDFLARE_ACCOUNT_ID=account-123');
    } finally {
        if ($original === null) {
            @unlink($path);
        } else {
            file_put_contents($path, $original);
        }
    }
});
