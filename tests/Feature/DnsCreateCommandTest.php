<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\Dns\CreateDnsRecord;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockResponse;

it('creates a dns record', function () {
    $zoneId = 'abcdef0123456789abcdef0123456789';

    mockCloudflare([
        CreateDnsRecord::class => MockResponse::make([
            'success' => true,
            'result' => [
                'id' => 'new-record-id',
                'type' => 'A',
                'name' => 'app.example.com',
                'content' => '9.9.9.9',
                'proxied' => true,
                'ttl' => 1,
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('dns:create', [
        'zone' => $zoneId,
        'type' => 'A',
        'name' => 'app',
        'content' => '9.9.9.9',
        '--proxied' => true,
    ]))->toBe(0)
        ->and(Artisan::output())
        ->toContain('DNS record created successfully!')
        ->toContain('new-record-id')
        ->toContain('app.example.com');
});

it('outputs the created record as json', function () {
    $zoneId = 'abcdef0123456789abcdef0123456789';

    mockCloudflare([
        CreateDnsRecord::class => MockResponse::make([
            'success' => true,
            'result' => [
                'id' => 'json-record-id',
                'type' => 'TXT',
                'name' => 'verify.example.com',
                'content' => 'token-value',
                'proxied' => false,
                'ttl' => 1,
            ],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('dns:create', [
        'zone' => $zoneId,
        'type' => 'TXT',
        'name' => 'verify',
        'content' => 'token-value',
        '--json' => true,
    ]))->toBe(0)
        ->and(Artisan::output())->toContain('"id": "json-record-id"');
});

it('fails when create dns returns an error', function () {
    $zoneId = 'abcdef0123456789abcdef0123456789';

    mockCloudflare([
        CreateDnsRecord::class => MockResponse::make([
            'success' => false,
            'errors' => [['message' => 'Invalid record']],
        ], 400),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('dns:create', [
        'zone' => $zoneId,
        'type' => 'A',
        'name' => 'bad',
        'content' => 'not-an-ip',
    ]))->toBe(1)
        ->and(Artisan::output())->toContain('Failed to create DNS record');
});
