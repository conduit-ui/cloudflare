<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\Dns\ListDnsRecords;
use App\Integrations\Cloudflare\Requests\Zones\ListZones;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockResponse;

it('lists dns records for a zone id', function () {
    $zoneId = 'abcdef0123456789abcdef0123456789';

    mockCloudflare([
        ListDnsRecords::class => MockResponse::make([
            'success' => true,
            'result' => [
                [
                    'id' => 'rec1',
                    'type' => 'A',
                    'name' => 'www.example.com',
                    'content' => '1.2.3.4',
                    'proxied' => true,
                    'ttl' => 1,
                ],
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('dns:list', ['zone' => $zoneId]))->toBe(0)
        ->and(Artisan::output())
        ->toContain('www.example.com')
        ->toContain('1.2.3.4');
});

it('resolves a domain name to a zone id before listing', function () {
    $zoneId = 'abcdef0123456789abcdef0123456789';

    mockCloudflare([
        ListZones::class => MockResponse::make([
            'success' => true,
            'result' => [
                ['id' => $zoneId, 'name' => 'example.com'],
            ],
        ], 200),
        ListDnsRecords::class => MockResponse::make([
            'success' => true,
            'result' => [
                [
                    'id' => 'rec1',
                    'type' => 'CNAME',
                    'name' => 'api.example.com',
                    'content' => 'target.example.net',
                    'proxied' => false,
                    'ttl' => 300,
                ],
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('dns:list', ['zone' => 'example.com', '--json' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('"name": "api.example.com"');
});

it('fails when the zone domain cannot be resolved', function () {
    mockCloudflare([
        ListZones::class => MockResponse::make([
            'success' => true,
            'result' => [],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('dns:list', ['zone' => 'missing.example']))->toBe(1)
        ->and(Artisan::output())->toContain('Zone not found: missing.example');
});
