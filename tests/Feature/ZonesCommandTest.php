<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\Zones\ListZones;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockResponse;

it('lists zones as a table', function () {
    mockCloudflare([
        ListZones::class => MockResponse::make([
            'success' => true,
            'result' => [
                [
                    'id' => 'zone1234567890abcdef1234567890ab',
                    'name' => 'example.com',
                    'status' => 'active',
                    'plan' => ['name' => 'Free Website'],
                ],
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('zones'))->toBe(0)
        ->and(Artisan::output())
        ->toContain('example.com')
        ->toContain('active')
        ->toContain('Free Website');
});

it('lists zones as json', function () {
    mockCloudflare([
        ListZones::class => MockResponse::make([
            'success' => true,
            'result' => [
                [
                    'id' => 'zone1234567890abcdef1234567890ab',
                    'name' => 'example.com',
                    'status' => 'active',
                    'plan' => ['name' => 'Free Website'],
                ],
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('zones', ['--json' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('"name": "example.com"');
});

it('reports when no zones are found', function () {
    mockCloudflare([
        ListZones::class => MockResponse::make([
            'success' => true,
            'result' => [],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('zones'))->toBe(0)
        ->and(Artisan::output())->toContain('No zones found.');
});

it('fails when the zones API returns an error', function () {
    mockCloudflare([
        ListZones::class => MockResponse::make([
            'success' => false,
            'errors' => [['message' => 'Unauthorized']],
        ], 401),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('zones'))->toBe(1)
        ->and(Artisan::output())->toContain('Failed to list zones');
});
