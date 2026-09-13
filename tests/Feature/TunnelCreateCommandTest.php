<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\Tunnels\CreateTunnel;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockResponse;

it('creates a tunnel', function () {
    mockCloudflare([
        CreateTunnel::class => MockResponse::make([
            'success' => true,
            'result' => [
                'id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
                'name' => 'showcase',
                'status' => 'inactive',
                'created_at' => '2026-09-10T00:00:00Z',
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('tunnel:create', ['name' => 'showcase']))->toBe(0)
        ->and(Artisan::output())
        ->toContain('Creating tunnel: showcase...')
        ->toContain('Tunnel created successfully!')
        ->toContain('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee');
});

it('outputs the created tunnel as json', function () {
    mockCloudflare([
        CreateTunnel::class => MockResponse::make([
            'success' => true,
            'result' => [
                'id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
                'name' => 'json-tunnel',
                'status' => 'inactive',
                'created_at' => '2026-09-10T00:00:00Z',
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('tunnel:create', ['name' => 'json-tunnel', '--json' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('"name": "json-tunnel"');
});

it('fails when create tunnel returns an error', function () {
    mockCloudflare([
        CreateTunnel::class => MockResponse::make([
            'success' => false,
            'errors' => [['message' => 'Name taken']],
        ], 409),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('tunnel:create', ['name' => 'taken']))->toBe(1)
        ->and(Artisan::output())->toContain('Failed to create tunnel');
});
