<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\Requests\Tunnels\DeleteTunnel;
use Illuminate\Support\Facades\Artisan;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('deletes a tunnel with --force', function () {
    mockCloudflare([
        DeleteTunnel::class => MockResponse::make([
            'success' => true,
            'result' => [],
        ], 200),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('tunnel:delete', [
        'id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        '--force' => true,
    ]))->toBe(0)
        ->and(Artisan::output())->toContain('Tunnel deleted successfully.');
});

it('cancels deletion when confirmation is declined', function () {
    MockClient::destroyGlobal();

    $this->artisan('tunnel:delete', [
        'id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
    ])
        ->expectsConfirmation('Delete tunnel aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee?', 'no')
        ->expectsOutputToContain('Cancelled.')
        ->assertSuccessful();
});

it('deletes after confirmation', function () {
    mockCloudflare([
        DeleteTunnel::class => MockResponse::make([
            'success' => true,
            'result' => [],
        ], 200),
    ]);

    $this->artisan('tunnel:delete', [
        'id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
    ])
        ->expectsConfirmation('Delete tunnel aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee?', 'yes')
        ->expectsOutputToContain('Tunnel deleted successfully.')
        ->assertSuccessful();
});

it('fails when delete tunnel returns an error', function () {
    mockCloudflare([
        DeleteTunnel::class => MockResponse::make([
            'success' => false,
            'errors' => [['message' => 'Not found']],
        ], 404),
    ]);

    $this->withoutMockingConsoleOutput();

    expect(Artisan::call('tunnel:delete', [
        'id' => 'missing-tunnel',
        '--force' => true,
    ]))->toBe(1)
        ->and(Artisan::output())->toContain('Failed to delete tunnel');
});
