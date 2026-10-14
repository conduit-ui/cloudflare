# Cloudflare CLI (`cf`) verification map

This directory is the maintained source for verifying user-facing behavior of `conduit-ui/cloudflare`. Read this index before driving the app, then use the matching feature file as the recipe.

## Baseline preconditions

- Repo root has PHP **>= 8.3** and a completed `composer install` (`vendor/autoload.php` present).
- Drive through `.cursor/skills/verify-cf/bin/verify-cf` with a pinned `CF_VERIFY_RUN_ID`.
- Run `bin/verify-cf doctor` and require `vendor=ok`, `entrypoint=ok`, `cf_list=ok`.
- Prefer process env `CLOUDFLARE_API_TOKEN` / `CLOUDFLARE_ACCOUNT_ID` over the project `.env`.
- Never run mutating `setup` against the developer's checkout — use `isolate-setup` first.
- Never drive a long-lived foreign process: this CLI is one-shot per invocation.
- Concurrent drives are safe for read-only commands; remote Cloudflare mutations on a shared account can collide.

## Driving conventions

- Start every recipe from baseline unless its preconditions say otherwise.
- Always pass `--json` on agent-facing commands (`zones`, `dns:*`, `tunnel:*`).
- Treat every command as literal. Keep flags and argument order unchanged.
- Capture evidence with `bin/verify-cf drive ...` (stdout, stderr, meta).
- Restore or delete remote fixtures you create (DNS records, tunnels). Keep proof artifacts.

## Proof and skip reporting

- Capture the user action (argv + exit code) and the resulting stdout JSON.
- Mutation proof includes a second user-facing read of the changed state.
- Record the feature ID and entry point with every artifact (filename slug is enough).
- Report an unreachable path with the attempted command and the unmet precondition (e.g. `live_creds=absent`).
- Do not report a skipped live entry point as verified through Pest mocks.

## Feature entry contract

Each feature file starts with an H1 title and one paragraph describing the user-visible behavior. It then uses exactly four H2 sections in this order:

1. `Sub-features`
2. `How to get to it (user POV)`
3. `Driving it with verify-cf`
4. `Gotchas`

## Features

- [Agent JSON / missing credentials](./agent-json.md) — stable `--json` failure envelope without creds.
- [Setup](./setup.md) — non-interactive credential onboarding (isolate tree).
- [Zones](./zones.md) — list zones with live API.
- [DNS list](./dns-list.md) — list DNS records for a zone.
- [Tunnel list](./tunnel-list.md) — list Cloudflare tunnels.
