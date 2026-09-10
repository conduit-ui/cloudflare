<?php

declare(strict_types=1);

it('builds the release PHAR on PHP 8.3', function () {
    $path = base_path('.github/workflows/release.yml');

    expect($path)->toBeFile();

    $yml = file_get_contents($path);

    expect($yml)->toContain("php-version: '8.3'")
        ->and($yml)->not->toContain("php-version: '8.4'");
});

it('exposes a composer build script for the PHAR', function () {
    $composer = json_decode(
        (string) file_get_contents(base_path('composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['scripts']['build'] ?? null)
        ->toBeString()
        ->and($composer['scripts']['build'])->toContain('app:build');
});

it('runs CI on PHP 8.3', function () {
    $yml = file_get_contents(base_path('.github/workflows/ci.yml'));

    expect($yml)->toContain("php-version: '8.3'")
        ->and($yml)->not->toContain("php-version: '8.4'");
});
