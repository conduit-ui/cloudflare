# SPEC: one `cf` PHAR on tag `v*`

Status: DRAFT

**Story:** README says download `cf` from GitHub Releases. Tag `v0.1.0` (2026-08-22) has zero assets. Pushing a `v*` tag does not build or upload a PHAR.

## Locked

- One binary named `cf`. Laravel Zero `app:build cf` writes `builds/cf`. Release uploads that file. Not `cf.phar`, not `cloudflare`.
- PHP 8.3 everywhere the pipeline runs: `composer.json` `require.php` `^8.3`, `config.platform.php` `8.3.33`, `.github/workflows/ci.yml`, `.github/workflows/release.yml`.
- `release.yml` triggers on push tags `v*` only. `shivammathur/setup-php@v2` with `php-version: '8.3'` and `ini-values: phar.readonly=0`. Build: `php cf app:build cf --build-version="${GITHUB_REF_NAME#v}"`. Upload: `softprops/action-gh-release@v2` `files: builds/cf`.
- Install is download `cf` from Releases, `chmod +x`, move onto `PATH`. Composer require and source clone stay. Do not add curl|bash, Homebrew, `install.sh`, or a second asset.
- You own (research): `specs/`, `~/.grok/tmp/9-notes.md`. Build owns `release.yml` `composer.json` `box.json` `.gitignore` README install section. Test owns `tests/`.
- Do not: merge, deploy, PHP 8.4, a second installer, touch `app/Commands` for this PR.

## Done when

- [ ] `rg "php-version: '8.3'" .github/workflows/release.yml` matches; `rg "php-version: '8.4'" .github/workflows/*.yml` is empty
- [ ] `rg -n "tags:|'v\\*'|files: builds/cf" .github/workflows/release.yml` shows tag `v*` and `files: builds/cf`
- [ ] `php -r 'echo json_decode(file_get_contents("composer.json"))->scripts->build, "\n";'` contains `app:build`
- [ ] `vendor/bin/pest --compact tests/Feature/PharReleaseTest.php` passes (8.3, not 8.4; composer build script present)

## Out of scope

- curl|bash installer, brew formula, `install.sh`, renaming the asset
- PHP 8.4 (Lexi caught `release.yml` on 8.4 at `1113c38`; `9fad191` / local `7e3b731` already moved it to 8.3 — keep it)
- Backfilling a PHAR onto `v0.1.0`
- Making GitHub Actions the house gate (quality-runners is)
- `app/Commands`, merge, deploy

## Open decisions

- None. Local `composer build` omits `--build-version` so Laravel Zero may prompt; tagged release already passes it. Do not add a second job to paper over that.
