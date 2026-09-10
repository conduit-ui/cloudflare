<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\CloudflareConnector;
use App\Integrations\Cloudflare\Requests\Tunnels\CreateTunnel;
use App\Integrations\Cloudflare\Requests\Tunnels\UpdateTunnelConfiguration;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

afterEach(function () {
    MockClient::destroyGlobal();
});

it('builds create tunnel request body with name and secret', function () {
    $request = new CreateTunnel('acct', 'my-tunnel');

    expect($request->resolveEndpoint())->toBe('/accounts/acct/cfd_tunnel');

    $body = (new ReflectionClass($request))
        ->getMethod('defaultBody')
        ->invoke($request);

    expect($body['name'])->toBe('my-tunnel')
        ->and($body['config_src'])->toBe('cloudflare')
        ->and($body['tunnel_secret'])->toBeString()
        ->and(strlen($body['tunnel_secret']))->toBeGreaterThan(10);
});

it('builds update tunnel configuration ingress body', function () {
    $ingress = [
        ['hostname' => 'app.example.com', 'service' => 'http://localhost:8000'],
        ['service' => 'http_status:404'],
    ];

    $request = new UpdateTunnelConfiguration('acct', 'tunnel-id', $ingress);

    expect($request->resolveEndpoint())
        ->toBe('/accounts/acct/cfd_tunnel/tunnel-id/configurations');

    $body = (new ReflectionClass($request))
        ->getMethod('defaultBody')
        ->invoke($request);

    expect($body['config']['ingress'])->toBe($ingress);
});

it('sends configure through the tunnel resource', function () {
    $mock = MockClient::global([
        UpdateTunnelConfiguration::class => MockResponse::make([
            'success' => true,
            'result' => ['ok' => true],
        ]),
    ]);

    $connector = new CloudflareConnector('token', 'acct');
    $response = $connector->tunnels()->configure('tunnel-id', [
        ['hostname' => 'app.example.com', 'service' => 'http://localhost:8000'],
        ['service' => 'http_status:404'],
    ]);

    expect($response->successful())->toBeTrue()
        ->and($response->json('result.ok'))->toBeTrue();

    $mock->assertSent(fn (Request $request) => $request instanceof UpdateTunnelConfiguration
        && $request->resolveEndpoint() === '/accounts/acct/cfd_tunnel/tunnel-id/configurations');
});
