<?php

declare(strict_types=1);

it('does not fake-green a quality-runners gate check from GitHub Actions', function () {
    $yml = file_get_contents(base_path('.github/workflows/ci.yml'));

    expect($yml)->toContain('name: CI')
        ->and($yml)->toContain('synapse-sentinel/quality-runners')
        ->and($yml)->toContain('vendor/bin/pest')
        ->and($yml)->toContain('vendor/bin/pint --test')
        ->and($yml)->not->toContain('name: quality-runners is the house gate')
        ->and($yml)->not->toContain('Pint + PHPStan + Pest');
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
