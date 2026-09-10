<?php

declare(strict_types=1);

function conduitRepoPath(string $relative = ''): string
{
    $root = dirname(__DIR__, 2);

    return $relative === '' ? $root : $root.DIRECTORY_SEPARATOR.$relative;
}

it('keeps README and conduit docs on disk', function () {
    expect(is_file(conduitRepoPath('README.md')))->toBeTrue()
        ->and(is_file(conduitRepoPath('docs/conduit.md')))->toBeTrue();
});

it('decodes composer.json', function () {
    $raw = file_get_contents(conduitRepoPath('composer.json'));

    expect($raw)->not->toBeFalse();

    json_decode($raw);

    expect(json_last_error())->toBe(JSON_ERROR_NONE);
});

it('does not link to docs/agent-output.md unless that file exists', function () {
    $files = array_values(array_filter(array_merge(
        [conduitRepoPath('README.md'), conduitRepoPath('AGENTS.md')],
        glob(conduitRepoPath('docs/*.md')) ?: [],
    ), 'is_file'));

    expect($files)->not->toBeEmpty();

    if (is_file(conduitRepoPath('docs/agent-output.md'))) {
        return;
    }

    foreach ($files as $file) {
        expect(str_contains((string) file_get_contents($file), 'docs/agent-output.md'))
            ->toBeFalse(basename($file).' must not link to missing docs/agent-output.md');
    }
});

it('documents tunnel:expose in docs/conduit.md', function () {
    expect(file_get_contents(conduitRepoPath('docs/conduit.md')))
        ->toContain('tunnel:expose');
});
