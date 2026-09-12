<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\User\VerifyToken;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    MockClient::destroyGlobal();
});

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

it('fails when token verification fails', function () {
    $path = base_path('.env');
    $original = file_exists($path) ? file_get_contents($path) : null;
    file_put_contents($path, "CLOUDFLARE_API_TOKEN=keep-me\n");

    MockClient::global([
        VerifyToken::class => MockResponse::make([
            'success' => false,
            'errors' => [
                ['message' => 'Invalid API Token'],
            ],
        ], 401),
    ]);

    try {
        $this->artisan('setup', [
            '--token' => 'bad-token',
            '--account-id' => 'account-123',
            '--non-interactive' => true,
        ])
            ->expectsOutputToContain('Invalid API Token')
            ->assertExitCode(1);

        expect(file_get_contents($path))->toBe("CLOUDFLARE_API_TOKEN=keep-me\n");
    } finally {
        if ($original === null) {
            @unlink($path);
        } else {
            file_put_contents($path, $original);
        }
    }
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

    $status = Artisan::call('setup', [
        '--token' => 'bad-token',
        '--account-id' => 'account-123',
        '--non-interactive' => true,
        '--json' => true,
    ]);
    $payload = json_decode(Artisan::output(), true);

    expect($status)->toBe(1)
        ->and($payload)->toBeArray()
        ->and($payload['success'])->toBeFalse()
        ->and($payload['message'])->toBe('Invalid API Token');
});

it('writes credentials to .env after a successful token verify', function () {
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
        $payload = json_decode($output, true);
        $env = file_get_contents($path);

        expect($status)->toBe(0)
            ->and($payload)->toBeArray()
            ->and($payload['success'])->toBeTrue()
            ->and($payload['message'])->toBe('Cloudflare credentials saved to .env')
            ->and($payload['account_id'])->toBe('account-123')
            ->and($output)->not->toContain('cf_good_token')
            ->and($env)->toContain('CLOUDFLARE_API_TOKEN=cf_good_token')
            ->and($env)->toContain('CLOUDFLARE_ACCOUNT_ID=account-123');
    } finally {
        if ($original === null) {
            @unlink($path);
        } else {
            file_put_contents($path, $original);
        }
    }
});
