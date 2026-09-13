<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\CloudflareConnector;
use App\Integrations\Cloudflare\Requests\Dns\DeleteDnsRecord;
use App\Integrations\Cloudflare\Requests\Dns\UpdateDnsRecord;
use App\Integrations\Cloudflare\Requests\Tunnels\GetTunnel;
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

function decodeCommandJson(string $output): array
{
    $trimmed = trim($output);
    $decoded = json_decode($trimmed, true);

    if (is_array($decoded)) {
        return $decoded;
    }

    $start = strpos($trimmed, '{');
    $end = strrpos($trimmed, '}');

    if ($start !== false && $end !== false && $end > $start) {
        $decoded = json_decode(substr($trimmed, $start, $end - $start + 1), true);

        if (is_array($decoded)) {
            return $decoded;
        }
    }

    expect($trimmed)->toBeJson();

    return [];
}

it('registers dns:delete, dns:update, and tunnel:get commands', function () {
    $this->artisan('list')
        ->expectsOutputToContain('dns:delete')
        ->expectsOutputToContain('dns:update')
        ->expectsOutputToContain('tunnel:get')
        ->assertSuccessful();
});

it('deletes a dns record with --force and --json', function () {
    $zoneId = 'abc123def456abc123def456abc123de';
    $recordId = 'rec11122233344455566677788899900';

    $mock = MockClient::global([
        DeleteDnsRecord::class => MockResponse::make([
            'success' => true,
            'result' => ['id' => $recordId],
        ], 200),
    ]);

    $exit = Artisan::call('dns:delete', [
        'zone' => $zoneId,
        'id' => $recordId,
        '--force' => true,
        '--json' => true,
    ]);
    $payload = decodeCommandJson(Artisan::output());

    expect($exit)->toBe(0)
        ->and($payload['ok'])->toBeTrue()
        ->and($payload['data']['id'])->toBe($recordId);

    $mock->assertSent(DeleteDnsRecord::class);
});

it('updates a dns record and prints a table', function () {
    $zoneId = 'abc123def456abc123def456abc123de';
    $recordId = 'rec11122233344455566677788899900';

    $mock = MockClient::global([
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

    $exit = Artisan::call('dns:update', [
        'zone' => $zoneId,
        'id' => $recordId,
        'type' => 'A',
        'name' => 'api',
        'content' => '5.6.7.8',
    ]);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('DNS record updated successfully!')
        ->and($output)->toContain('Field')
        ->and($output)->toContain('Value')
        ->and($output)->toContain($recordId)
        ->and($output)->toContain('Type')
        ->and($output)->toContain('A')
        ->and($output)->toContain('Name')
        ->and($output)->toContain('api.example.com')
        ->and($output)->toContain('Content')
        ->and($output)->toContain('5.6.7.8')
        ->and($output)->toContain('Proxied')
        ->and($output)->toContain('No')
        ->and($output)->toContain('TTL')
        ->and($output)->toContain('Auto')
        ->and($output)->not->toContain('"id":');

    $mock->assertSent(UpdateDnsRecord::class);
});

it('gets a tunnel and supports --json', function () {
    $tunnelId = 'tun-aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    $mock = MockClient::global([
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

    $exit = Artisan::call('tunnel:get', [
        'id' => $tunnelId,
        '--json' => true,
    ]);
    $payload = decodeCommandJson(Artisan::output());

    expect($exit)->toBe(0)
        ->and($payload['ok'])->toBeTrue()
        ->and($payload['data']['id'])->toBe($tunnelId)
        ->and($payload['data']['name'])->toBe('my-app')
        ->and($payload['data']['status'])->toBe('healthy')
        ->and($payload['data']['created_at'])->toBe('2024-01-15T12:00:00Z')
        ->and($payload['data']['connections'])->toHaveCount(1)
        ->and($payload['data']['connections'][0]['id'])->toBe('c1');

    $mock->assertSent(GetTunnel::class);
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
