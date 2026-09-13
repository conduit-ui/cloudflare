<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class)
    ->beforeEach(function () {
        MockClient::destroyGlobal();
        fakeCloudflareCredentials();
        $this->mockConsoleOutput = true;
    })
    ->afterEach(function () {
        MockClient::destroyGlobal();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function fakeCloudflareCredentials(): void
{
    putenv('CLOUDFLARE_API_TOKEN=test-token');
    putenv('CLOUDFLARE_ACCOUNT_ID=test-account-id');
    $_ENV['CLOUDFLARE_API_TOKEN'] = 'test-token';
    $_ENV['CLOUDFLARE_ACCOUNT_ID'] = 'test-account-id';
}

/**
 * @param  array<string, MockResponse|callable>  $responses
 */
function mockCloudflare(array $responses): MockClient
{
    MockClient::destroyGlobal();

    return MockClient::global($responses);
}
