# AGENTS.md

Short guide for coding agents working in `conduit-ui/cloudflare`.

## What this repo is

Laravel Zero CLI (`./cf`) for Cloudflare zones, DNS, and tunnels. Part of the Conduit agent tooling set — see [docs/conduit.md](docs/conduit.md). Demo session: [docs/demo.md](docs/demo.md).

## Local loop

```bash
composer install
cp .env.example .env   # set CLOUDFLARE_API_TOKEN / CLOUDFLARE_ACCOUNT_ID for live API calls
./bin/ci
```

`./bin/ci` is Pint `--test`, PHPStan, Rector dry-run, and Pest. That matches [quality-runners](https://github.com/synapse-sentinel/quality-runners). GitHub Actions still runs Pint `--test` and Pest as a residual.

## Layout

- `app/Commands/` — Artisan commands (`zones`, `dns:*`, `tunnel:*` including `tunnel:expose`)
- `app/Integrations/Cloudflare/` — Saloon connector, resources, requests
- `tests/` — Pest feature/unit tests
- `docs/conduit.md` — agent recipes
- `docs/demo.md` / `docs/demo.cast.md` — walkthrough and sample session

## Conventions

- Prefer `--json` in examples and agent-facing docs. That flag prints pretty-printed JSON of the API `result` on stdout. Lists are arrays; creates are objects. Failures are a human error line and a non-zero exit.
- `tunnel:create` / `tunnel:expose --json` include create-time `token` and `credentials_file`. Treat that stdout as secret.
- Keep API credentials in env; never hardcode tokens.
- New Cloudflare endpoints: Saloon request + resource method + command; mirror existing `--json` / table patterns.
- Avoid breaking command signatures; additive flags are fine.
- Docs-only / packaging PRs should not change runtime behavior.

## Tests

Add feature tests for new commands when practical; keep unit tests free of live Cloudflare calls unless mocked.
