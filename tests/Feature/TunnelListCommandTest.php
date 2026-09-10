<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\Tunnels\ListTunnels;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockResponse;

it('lists tunnels as a table', function () {
    mockCloudflare([
        ListTunnels::class => MockResponse::make([
            'success' => true,
            'result' => [
                [
                    'id' => '11111111-2222-3333-4444-555555555555',
                    'name' => 'demo-tunnel',
                    'status' => 'healthy',
                    'created_at' => '2026-01-15T12:00:00Z',
                    'connections' => [['id' => 'c1'], ['id' => 'c2']],
                ],
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('tunnel:list'))->toBe(0)
        ->and(Artisan::output())
        ->toContain('demo-tunnel')
        ->toContain('healthy');
});

it('lists tunnels as json', function () {
    mockCloudflare([
        ListTunnels::class => MockResponse::make([
            'success' => true,
            'result' => [
                [
                    'id' => '11111111-2222-3333-4444-555555555555',
                    'name' => 'demo-tunnel',
                    'status' => 'healthy',
                    'created_at' => '2026-01-15T12:00:00Z',
                    'connections' => [],
                ],
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('tunnel:list', ['--json' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('"name": "demo-tunnel"');
});

it('reports when no tunnels are found', function () {
    mockCloudflare([
        ListTunnels::class => MockResponse::make([
            'success' => true,
            'result' => [],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('tunnel:list'))->toBe(0)
        ->and(Artisan::output())->toContain('No tunnels found.');
});
