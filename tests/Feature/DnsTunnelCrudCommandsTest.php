<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\CloudflareConnector;
use App\Integrations\Cloudflare\Requests\Dns\DeleteDnsRecord;
use App\Integrations\Cloudflare\Requests\Dns\UpdateDnsRecord;
use App\Integrations\Cloudflare\Requests\Tunnels\GetTunnel;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function () {
    MockClient::destroyGlobal();
});

it('registers dns:delete, dns:update, and tunnel:get commands', function () {
    $this->artisan('list')
        ->expectsOutputToContain('dns:delete')
        ->expectsOutputToContain('dns:update')
        ->expectsOutputToContain('tunnel:get')
        ->assertSuccessful();
});

it('deletes a dns record with --force and --json', function () {
    putenv('CLOUDFLARE_API_TOKEN=test-token');
    putenv('CLOUDFLARE_ACCOUNT_ID=test-account');
    $_ENV['CLOUDFLARE_API_TOKEN'] = 'test-token';
    $_ENV['CLOUDFLARE_ACCOUNT_ID'] = 'test-account';

    $zoneId = 'abc123def456abc123def456abc123de';
    $recordId = 'rec11122233344455566677788899900';

    MockClient::global([
        DeleteDnsRecord::class => MockResponse::make([
            'success' => true,
            'result' => ['id' => $recordId],
        ], 200),
    ]);

    $this->artisan('dns:delete', [
        'zone' => $zoneId,
        'id' => $recordId,
        '--force' => true,
        '--json' => true,
    ])
        ->expectsOutputToContain($recordId)
        ->assertSuccessful();
});

it('updates a dns record and prints a table', function () {
    putenv('CLOUDFLARE_API_TOKEN=test-token');
    $_ENV['CLOUDFLARE_API_TOKEN'] = 'test-token';

    $zoneId = 'abc123def456abc123def456abc123de';
    $recordId = 'rec11122233344455566677788899900';

    MockClient::global([
        UpdateDnsRecord::class => MockResponse::make([
            'success' => true,
            'result' => [
                'id' => $recordId,
                'type' => 'A',
                'name' => 'api.example.com',
                'content' => '5.6.7.8',
                'proxied' => false,
                'ttl' => 1,
            ],
        ], 200),
    ]);

    $this->artisan('dns:update', [
        'zone' => $zoneId,
        'id' => $recordId,
        'type' => 'A',
        'name' => 'api',
        'content' => '5.6.7.8',
    ])
        ->expectsOutputToContain('DNS record updated successfully!')
        ->expectsOutputToContain($recordId)
        ->assertSuccessful();
});

it('gets a tunnel and supports --json', function () {
    putenv('CLOUDFLARE_API_TOKEN=test-token');
    putenv('CLOUDFLARE_ACCOUNT_ID=test-account');
    $_ENV['CLOUDFLARE_API_TOKEN'] = 'test-token';
    $_ENV['CLOUDFLARE_ACCOUNT_ID'] = 'test-account';

    $tunnelId = 'tun-aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    MockClient::global([
        GetTunnel::class => MockResponse::make([
            'success' => true,
            'result' => [
                'id' => $tunnelId,
                'name' => 'my-app',
                'status' => 'healthy',
                'created_at' => '2024-01-15T12:00:00Z',
                'connections' => [['id' => 'c1']],
            ],
        ], 200),
    ]);

    $this->artisan('tunnel:get', [
        'id' => $tunnelId,
        '--json' => true,
    ])->assertSuccessful();
});

it('builds update, delete, and get request endpoints', function () {
    $update = new UpdateDnsRecord('zone1', 'rec1', 'A', 'api', '1.2.3.4', true, 300);
    expect($update->resolveEndpoint())->toBe('/zones/zone1/dns_records/rec1');

    $delete = new DeleteDnsRecord('zone1', 'rec1');
    expect($delete->resolveEndpoint())->toBe('/zones/zone1/dns_records/rec1');

    $get = new GetTunnel('acct1', 'tun1');
    expect($get->resolveEndpoint())->toBe('/accounts/acct1/cfd_tunnel/tun1');
});

it('wires DnsResource::update through the connector', function () {
    $mock = new MockClient([
        UpdateDnsRecord::class => MockResponse::make([
            'success' => true,
            'result' => ['id' => 'rec1'],
        ], 200),
    ]);

    $connector = new CloudflareConnector('token', 'account');
    $connector->withMockClient($mock);

    $response = $connector->dns('zone1')->update('rec1', 'A', 'api', '9.9.9.9');

    expect($response->successful())->toBeTrue()
        ->and($response->json('result.id'))->toBe('rec1');

    $mock->assertSent(UpdateDnsRecord::class);
});
