<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\Tunnels\CreateTunnel;
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

function setCloudflareTestEnv(?string $token, ?string $accountId): void
{
    if ($token === null || $token === '') {
        putenv('CLOUDFLARE_API_TOKEN');
        $_ENV['CLOUDFLARE_API_TOKEN'] = '';
        unset($_SERVER['CLOUDFLARE_API_TOKEN']);
    } else {
        putenv('CLOUDFLARE_API_TOKEN='.$token);
        $_ENV['CLOUDFLARE_API_TOKEN'] = $token;
        $_SERVER['CLOUDFLARE_API_TOKEN'] = $token;
    }

    if ($accountId === null || $accountId === '') {
        putenv('CLOUDFLARE_ACCOUNT_ID');
        $_ENV['CLOUDFLARE_ACCOUNT_ID'] = '';
        unset($_SERVER['CLOUDFLARE_ACCOUNT_ID']);
    } else {
        putenv('CLOUDFLARE_ACCOUNT_ID='.$accountId);
        $_ENV['CLOUDFLARE_ACCOUNT_ID'] = $accountId;
        $_SERVER['CLOUDFLARE_ACCOUNT_ID'] = $accountId;
    }
}

it('returns failure JSON envelope when API token is missing', function () {
    setCloudflareTestEnv(null, null);

    $status = Artisan::call('zones', ['--json' => true]);
    $payload = decodeAgentJson(Artisan::output());

    expect($status)->toBe(1)
        ->and($payload['ok'])->toBeFalse()
        ->and($payload)->not->toHaveKey('data')
        ->and($payload['error'])->toBe('CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set')
        ->and($payload['error'])->not->toBe('CLOUDFLARE_API_TOKEN not set');
});

it('returns success JSON envelope for zones with mocked HTTP', function () {
    setCloudflareTestEnv('test-token', 'test-account');

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
        ->and($payload)->not->toHaveKey('error')
        ->and($payload['data'])->toBe($zones);
});

it('returns cancelled JSON envelope when tunnel:delete is not confirmed', function () {
    setCloudflareTestEnv('test-token', 'test-account');

    $status = Artisan::call('tunnel:delete', [
        'id' => 'tun-1',
        '--json' => true,
        '--no-interaction' => true,
    ]);
    $payload = decodeAgentJson(Artisan::output());

    expect($status)->toBe(0)
        ->and($payload['ok'])->toBeTrue()
        ->and($payload)->not->toHaveKey('error')
        ->and($payload['data'])->toMatchArray([
            'deleted' => false,
            'cancelled' => true,
        ]);
});

it('returns failure JSON envelope when Cloudflare API errors', function () {
    setCloudflareTestEnv('test-token', 'test-account');

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
        ->and($payload)->not->toHaveKey('data')
        ->and($payload['error'])->toContain('Failed to list zones');
});

it('wraps tunnel:expose --json as an envelope around the existing payload', function () {
    setCloudflareTestEnv('test-token', 'test-account');

    $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.demo-token';

    MockClient::global([
        CreateTunnel::class => MockResponse::make([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [
                'id' => 'c1744f8b-faa1-48a4-9e5c-02ac921467fa',
                'name' => 'demo-app',
                'status' => 'inactive',
                'created_at' => '2025-02-18T22:41:43.534395Z',
                'token' => $token,
                'credentials_file' => [
                    'AccountTag' => 'account-tag',
                    'TunnelID' => 'c1744f8b-faa1-48a4-9e5c-02ac921467fa',
                    'TunnelName' => 'demo-app',
                    'TunnelSecret' => 'super-secret-tunnel-value',
                ],
            ],
        ]),
    ]);

    $status = Artisan::call('tunnel:expose', [
        'name' => 'demo-app',
        '--json' => true,
    ]);
    $output = Artisan::output();
    $payload = decodeAgentJson($output);

    expect($status)->toBe(0)
        ->and($payload['ok'])->toBeTrue()
        ->and($payload)->not->toHaveKey('error')
        ->and(array_keys($payload['data']))->toBe([
            'tunnel',
            'hostname',
            'url',
            'ingress_configured',
            'dns_created',
            'dns_record',
            'configuration',
            'dns_route_command',
        ])
        ->and($payload['data']['url'])->toBe('http://localhost:8000')
        ->and($payload['data']['tunnel']['token'])->toBe($token)
        ->and($output)->toContain('localhost:8000')
        ->and($output)->toContain('"token": "'.$token.'"');
});
