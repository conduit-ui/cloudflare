<?php

declare(strict_types=1);

use App\Integrations\Cloudflare\CloudflareConnector;

it('resolves the Cloudflare API base URL and auth header', function () {
    $connector = new CloudflareConnector('test-token', 'acct-123');

    expect($connector->resolveBaseUrl())->toBe('https://api.cloudflare.com/client/v4')
        ->and($connector->getAccountId())->toBe('acct-123');
});

it('does not ship the Laravel Zero InspireCommand scaffold', function () {
    expect(is_file(dirname(__DIR__, 2).'/app/Commands/InspireCommand.php'))->toBeFalse();
});

it('pins composer platform php to 8.3.33', function () {
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

    expect($composer['config']['platform']['php'])->toBe('8.3.33');
});
