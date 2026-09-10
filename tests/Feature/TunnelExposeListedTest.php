<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('lists tunnel:expose on the CLI', function () {
    $exit = Artisan::call('list');

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toContain('tunnel:expose');
});

it('prints tunnel:expose help', function () {
    $exit = Artisan::call('tunnel:expose', ['--help' => true]);

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toContain('Create a tunnel and print cloudflared steps to expose a local app');
});
