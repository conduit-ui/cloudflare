<?php

declare(strict_types=1);

it('points CI at quality-runners instead of running tools on GitHub Actions', function () {
    $yml = file_get_contents(base_path('.github/workflows/ci.yml'));

    expect($yml)->toContain('name: CI')
        ->and($yml)->toContain('synapse-sentinel/quality-runners')
        ->and($yml)->toContain('quality-runners is the house gate')
        ->and($yml)->not->toContain('Pint + PHPStan + Pest')
        ->and($yml)->not->toContain('actions/checkout')
        ->and($yml)->not->toContain('setup-php')
        ->and($yml)->not->toContain('./bin/ci')
        ->and($yml)->not->toContain('vendor/bin/pint')
        ->and($yml)->not->toContain('vendor/bin/phpstan')
        ->and($yml)->not->toContain('vendor/bin/pest')
        ->and($yml)->not->toContain('vendor/bin/rector');
});

it('does not ship a synapse-sentinel/gate Actions workflow', function () {
    $workflows = array_merge(
        glob(base_path('.github/workflows/*.yml')) ?: [],
        glob(base_path('.github/workflows/*.yaml')) ?: [],
    );

    expect($workflows)->not->toBeEmpty();

    foreach ($workflows as $workflow) {
        expect(file_get_contents($workflow))
            ->not->toContain('synapse-sentinel/gate')
            ->and(basename($workflow))->not->toBe('gate.yml');
    }
});
