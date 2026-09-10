<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\Dns\CreateDnsRecord;
use App\Integrations\Cloudflare\Requests\Tunnels\CreateTunnel;
use App\Integrations\Cloudflare\Requests\Tunnels\UpdateTunnelConfiguration;
use App\Integrations\Cloudflare\Requests\Zones\ListZones;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    putenv('CLOUDFLARE_API_TOKEN=test-token');
    putenv('CLOUDFLARE_ACCOUNT_ID=test-account');
    $_ENV['CLOUDFLARE_API_TOKEN'] = 'test-token';
    $_ENV['CLOUDFLARE_ACCOUNT_ID'] = 'test-account';
    $_SERVER['CLOUDFLARE_API_TOKEN'] = 'test-token';
    $_SERVER['CLOUDFLARE_ACCOUNT_ID'] = 'test-account';

    MockClient::destroyGlobal();
});

afterEach(function () {
    MockClient::destroyGlobal();
});

function fakeTunnelCreateResponse(array $overrides = []): array
{
    return array_replace_recursive([
        'success' => true,
        'errors' => [],
        'messages' => [],
        'result' => [
            'id' => 'c1744f8b-faa1-48a4-9e5c-02ac921467fa',
            'name' => 'demo-app',
            'status' => 'inactive',
            'created_at' => '2025-02-18T22:41:43.534395Z',
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.demo-token',
            'credentials_file' => [
                'AccountTag' => 'account-tag',
                'TunnelID' => 'c1744f8b-faa1-48a4-9e5c-02ac921467fa',
                'TunnelName' => 'demo-app',
                'TunnelSecret' => 'super-secret-tunnel-value',
            ],
        ],
    ], $overrides);
}

it('creates a tunnel and surfaces token and credentials', function () {
    MockClient::global([
        CreateTunnel::class => MockResponse::make(fakeTunnelCreateResponse()),
    ]);

    $exit = Artisan::call('tunnel:create', ['name' => 'demo-app']);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('Tunnel created successfully!')
        ->and($output)->toContain('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.demo-token')
        ->and($output)->toContain('super-secret-tunnel-value')
        ->and($output)->toContain('cloudflared tunnel run --token');
});

it('dumps the full create result as json', function () {
    MockClient::global([
        CreateTunnel::class => MockResponse::make(fakeTunnelCreateResponse()),
    ]);

    $exit = Artisan::call('tunnel:create', ['name' => 'demo-app', '--json' => true]);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('"token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.demo-token"')
        ->and($output)->toContain('"TunnelSecret": "super-secret-tunnel-value"');
});

it('exposes a local app with hostname routing via API', function () {
    MockClient::global([
        CreateTunnel::class => MockResponse::make(fakeTunnelCreateResponse()),
        UpdateTunnelConfiguration::class => MockResponse::make([
            'success' => true,
            'result' => ['tunnel_id' => 'c1744f8b-faa1-48a4-9e5c-02ac921467fa'],
        ]),
        ListZones::class => MockResponse::make([
            'success' => true,
            'result' => [['id' => 'zone-123', 'name' => 'example.com']],
        ]),
        CreateDnsRecord::class => MockResponse::make([
            'success' => true,
            'result' => [
                'id' => 'dns-1',
                'type' => 'CNAME',
                'name' => 'app.example.com',
                'content' => 'c1744f8b-faa1-48a4-9e5c-02ac921467fa.cfargotunnel.com',
            ],
        ]),
    ]);

    $exit = Artisan::call('tunnel:expose', [
        'name' => 'demo-app',
        'hostname' => 'app.example.com',
        'url' => 'http://localhost:3000',
    ]);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('Tunnel ready to expose your app!')
        ->and($output)->toContain('Ingress configured: app.example.com → http://localhost:3000')
        ->and($output)->toContain('DNS CNAME created: app.example.com')
        ->and($output)->toContain('cloudflared tunnel run --token eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.demo-token');
});

it('prints dns route command when zone lookup fails', function () {
    MockClient::global([
        CreateTunnel::class => MockResponse::make(fakeTunnelCreateResponse()),
        UpdateTunnelConfiguration::class => MockResponse::make([
            'success' => true,
            'result' => [],
        ]),
        ListZones::class => MockResponse::make([
            'success' => true,
            'result' => [],
        ]),
    ]);

    $exit = Artisan::call('tunnel:expose', [
        'name' => 'demo-app',
        'hostname' => 'app.unknown.test',
    ]);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('cloudflared tunnel route dns demo-app app.unknown.test');
});

it('defaults the local service URL to http://localhost:8000', function () {
    MockClient::global([
        CreateTunnel::class => MockResponse::make(fakeTunnelCreateResponse()),
    ]);

    $exit = Artisan::call('tunnel:expose', [
        'name' => 'demo-app',
        '--json' => true,
    ]);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('localhost:8000')
        ->and($output)->toContain('"token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.demo-token"');
});

it('reports API failures from tunnel:create', function () {
    MockClient::global([
        CreateTunnel::class => MockResponse::make([
            'success' => false,
            'errors' => [['message' => 'tunnel name already exists']],
            'result' => null,
        ], 400),
    ]);

    $exit = Artisan::call('tunnel:create', ['name' => 'taken']);
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('Failed to create tunnel:');
});
