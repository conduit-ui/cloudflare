# SPEC: Scaffold cleanup (LICENSE, drop Inspire, app name Cloudflare)

Status: DRAFT

**Story:** README and `composer.json` say this is a Cloudflare CLI under MIT. `cf list` still ships Laravel Zero's `inspire` and the app name `Cf`, and there is no `LICENSE` file. Cursor PR https://github.com/conduit-ui/cloudflare/pull/3 almost fixed that, then sat one commit behind `#17` (quality-runners) with a body that still claims a CI/lock rewrite.

## Locked

- Apply the cleanup **on current `main`** (`e60415d` / `#17`). Rebase, do not replay the pre-`#17` tree.
- Add MIT `LICENSE` (Copyright (c) 2026 Jordan Partridge).
- Delete `app/Commands/InspireCommand.php` and `tests/Feature/InspireCommandTest.php`.
- `config/app.php` `'name'` → `Cloudflare`. Binary stays `cf`. Keep the `#17` `AppServiceProvider` import.
- Drop nonexistent `Database\\Factories\\` / `Database\\Seeders\\` autoload paths. There is no `database/` directory.
- Replace `tests/Unit/ExampleTest.php` with a `CloudflareConnector` smoke test.
- `composer.json` `config.platform.php` stays `"8.3.33"`. `composer.lock` `platform-overrides.php` stays `"8.3.33"`. Host PHP here is 8.5.x; unconstrained `composer update` resolves past 8.3 and breaks the 8.3 CI runner (closed PR `#12`).
- Keep `phpstan/phpstan` and `rector/rector` in `require-dev` (added by `#17` for `./bin/ci`). Autoload-only edits do **not** change Composer content-hash; do not regenerate the lock.
- Keep `tunnel:expose`: `app/Commands/TunnelExposeCommand.php` + `tests/Feature/TunnelExposeCommandTest.php`.
- Keep `tests/Feature/QualityRunnersCiTest.php` and the residual `.github/workflows/ci.yml` from `#17` (Pint `--test` + Pest; not a fake `quality-runners/gate` job).
- Research owns `specs/` and `~/.grok/tmp/3-notes.md`. Build owns LICENSE / Inspire delete / `config/app.php` / composer autoload. Test owns `tests/Unit/CloudflareConnectorTest.php`.
- Do not merge. Do not deploy.

## Done when

- [ ] `test ! -f app/Commands/InspireCommand.php && test ! -f tests/Feature/InspireCommandTest.php`
- [ ] `python3 -c "import json; assert json.load(open('composer.json'))['config']['platform']['php']=='8.3.33'"`
- [ ] `python3 -c "import json; c=json.load(open('composer.json')); assert 'phpstan/phpstan' in c['require-dev'] and 'rector/rector' in c['require-dev']"`
- [ ] `test -f app/Commands/TunnelExposeCommand.php && test -f tests/Feature/TunnelExposeCommandTest.php && test -f tests/Feature/QualityRunnersCiTest.php && test -f LICENSE`
- [ ] `rg -n "'name' => 'Cloudflare'" config/app.php`
- [ ] After `composer install`: `vendor/bin/pest --compact` exits 0. `php cf list` does not show `inspire`. `php cf tunnel:expose --help` exits 0.

## Out of scope

- Merging PR `#3` or deploying a PHAR
- Regenerating `composer.lock`, bumping Symfony, pinning PHP 8.4/8.5
- Restoring a full GHA quality workflow (PR body is stale; `#17` already retired that)
- `dns:upsert` / `tunnel:status` / `--dry-run` (open issues `#14` `#15` `#16`)
- Daily clone under `~/Projects/conduit-ui/repos/cloudflare`

## Open decisions

- `tests/Feature/.gitkeep` is empty noise (`fc7ba34` commit message still says "regenerate composer.lock"; the file is the whole commit). Optional delete. Do not empty `tests/Feature/` to justify it.
- Smoke test title says "auth header" but only asserts base URL + account id. Optional rename or assert `Authorization` via Saloon public headers. Not blocking.
- PR body still claims "Add CI workflow" and "Refresh composer.lock". Update the body; do not implement those claims.
