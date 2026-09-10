<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\Zones\ListZones;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    MockClient::destroyGlobal();
});

afterEach(function () {
    MockClient::destroyGlobal();
});

function decodeAgentJson(string $output): array
{
    $decoded = json_decode($output, true);
    expect($decoded)->toBeArray();

    return $decoded;
}

it('returns failure JSON envelope when API token is missing', function () {
    putenv('CLOUDFLARE_API_TOKEN');
    putenv('CLOUDFLARE_ACCOUNT_ID');
    $_ENV['CLOUDFLARE_API_TOKEN'] = '';
    $_ENV['CLOUDFLARE_ACCOUNT_ID'] = '';
    unset($_SERVER['CLOUDFLARE_API_TOKEN'], $_SERVER['CLOUDFLARE_ACCOUNT_ID']);

    $status = Artisan::call('zones', ['--json' => true]);
    $payload = decodeAgentJson(Artisan::output());

    expect($status)->toBe(1)
        ->and($payload['ok'])->toBeFalse()
        ->and($payload['error'])->toContain('CLOUDFLARE_API_TOKEN not set');
});

it('returns success JSON envelope for zones with mocked HTTP', function () {
    putenv('CLOUDFLARE_API_TOKEN=test-token');
    putenv('CLOUDFLARE_ACCOUNT_ID=test-account');
    $_ENV['CLOUDFLARE_API_TOKEN'] = 'test-token';
    $_ENV['CLOUDFLARE_ACCOUNT_ID'] = 'test-account';

    $zones = [
        [
            'id' => 'abc123abc123abc123abc123abc123ab',
            'name' => 'example.com',
            'status' => 'active',
            'plan' => ['name' => 'Free'],
        ],
    ];

    MockClient::global([
        ListZones::class => MockResponse::make([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => $zones,
        ], 200),
    ]);

    $status = Artisan::call('zones', ['--json' => true]);
    $payload = decodeAgentJson(Artisan::output());

    expect($status)->toBe(0)
        ->and($payload['ok'])->toBeTrue()
        ->and($payload['data'])->toBe($zones);
});

it('returns failure JSON envelope when Cloudflare API errors', function () {
    putenv('CLOUDFLARE_API_TOKEN=test-token');
    putenv('CLOUDFLARE_ACCOUNT_ID=test-account');
    $_ENV['CLOUDFLARE_API_TOKEN'] = 'test-token';
    $_ENV['CLOUDFLARE_ACCOUNT_ID'] = 'test-account';

    MockClient::global([
        ListZones::class => MockResponse::make([
            'success' => false,
            'errors' => [['message' => 'Authentication error']],
            'result' => null,
        ], 401),
    ]);

    $status = Artisan::call('zones', ['--json' => true]);
    $payload = decodeAgentJson(Artisan::output());

    expect($status)->toBe(1)
        ->and($payload['ok'])->toBeFalse()
        ->and($payload['error'])->toContain('Failed to list zones');
});
