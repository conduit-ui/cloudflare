# SPEC: Agent JSON envelope (`--json`)

Status: **LOCKED**
PR: https://github.com/conduit-ui/cloudflare/pull/7
Repo: `conduit-ui/cloudflare`

**Story:** Agents pass `--json` and still get a table, a raw Cloudflare `result`, or a process that `exit(1)`s before an envelope. Human mode (no `--json`) already works. The lie is machine stdout, not the tables.

## Locked

- `--json` stdout is exactly one envelope:
  - success → `{"ok":true,"data":...}` exit `0`
  - failure → `{"ok":false,"error":"..."}` exit `1`
- Success has `ok` + `data` only (no `error` key). Failure has `ok` + `error` only (no `data` key).
- Pretty-print: `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES`. Both envelopes go to **stdout** (`$this->line`).
- Human mode (no `--json`) is unchanged: tables / info / `displayTunnelCredentials` / cloudflared steps. Failures use `$this->error()`.
- Implementation: `InteractsWithCloudflare` + `OutputsJson`. Commands keep extending `LaravelZero\Framework\Commands\Command`.
- **Do not** add `app/Commands/CloudflareCommand.php`. PR #6 is parked; it fights this one (base `connector()` vs trait `getConnector()`, raw `json_encode`, `$this->error` on fail, token-only auth).
- **No `exit(1)`** (or any `exit(`) under `app/Commands`. `getConnector(): ?CloudflareConnector` prints once, returns `null`; `handle()` returns `self::FAILURE`. Do not print the error twice.
- Missing creds string (token **or** account id): `CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set`.
- `jsonFail` / `jsonSuccess` / `wantsJson` live only in `OutputsJson`. Trait `getConnector` calls `jsonFail` when that method exists, else `$this->error`.
- `--json` must not print tables, "Creating tunnel…", "Cancelled.", or mixed prose.

## Commands (`--json` `data`)

| Command | `data` on success | Fail |
|---------|-------------------|------|
| `zones` | API `result` (zone list) | list error |
| `dns:list` | API `result` (records) | zone not found / list error |
| `dns:create` | API `result` (record) | zone not found / create error |
| `tunnel:list` | API `result` (tunnels) | list error |
| `tunnel:create` | API `result` (tunnel, including token / credentials_file) | create error |
| `tunnel:delete` | `{deleted:true,id}` or **cancelled** `{deleted:false,cancelled:true}` | delete error |
| `tunnel:expose` | wrap the **current** object (do not invent keys): `tunnel`, `hostname`, `url`, `ingress_configured`, `dns_created`, `dns_record`, `configuration`, `dns_route_command` | create error |

Cancelled `tunnel:delete` (`--no-interaction` → `confirm()` false, or user says no): **success** envelope, exit `0`. Agents read `data.deleted`, not `ok`.

Partial `tunnel:expose` (tunnel created, DNS/ingress skipped): **success** envelope, flags stay false. Human warnings stay human-only.

`inspire` has no `--json`. Out of scope.

## Shape (build)

Model: `TunnelCreateCommand` / `TunnelDeleteCommand` (both traits, no local `getConnector`).

Still wrong on the rebased branch:

1. `ZonesCommand`, `DnsListCommand`, `DnsCreateCommand`, `TunnelListCommand` — drop the local `getConnector`. `use InteractsWithCloudflare` + `use OutputsJson`. Keep each command's `resolveZoneId` / `truncate` (do not steal PR #6's base class for that).
2. `TunnelExposeCommand` — `use OutputsJson`. Replace the raw `json_encode(...)` with `jsonSuccess($payload)`. Create failure → `jsonFail(...)`. Missing token with `--json` must envelope (today it only `$this->error` because the trait's `jsonFail` is missing).
3. `docs/agent-output.md` must list `tunnel:expose`. README must not say "raw API result" for `--json`.
4. Rebase conflicts: take `jsonFail` / `jsonSuccess`, not `$this->error` + bare `json_encode`. Keep main's quality-runners CI (`#17`). Do not resurrect GHA-as-gate.

You own: `app/Commands/Concerns/OutputsJson.php`, JSON wiring in `app/Commands/*`, `docs/agent-output.md`.
Do not: merge, deploy, `CloudflareCommand`, Saloon resource rewrites, `--dry-run` / `dns:upsert` / `tunnel:status`.

## Shape (test)

You own: `tests/Feature/AgentJsonOutputTest.php` and JSON proves.

Must cover:

1. Missing token → `zones --json` → `ok: false`, exit `1`, `error` contains `CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set` (not `CLOUDFLARE_API_TOKEN not set` — that string is the duplicate `getConnector`).
2. Success envelope → mocked `zones --json` → `ok: true`, `data` is the API `result`.
3. `tunnel:delete --json --no-interaction` → `ok: true`, `deleted: false`, `cancelled: true`, exit `0`.
4. API error → `ok: false`, exit `1` (existing zones 401 case).
5. Optional but in-contract: `tunnel:expose --json` decodes as envelope (`ok` + `data.tunnel.token`); do **not** rewrite `TunnelExposeCommandTest` assertions that `toContain` token / `localhost:8000` — wrapping must keep those green.

`vendor/bin/pest --compact` must stay green with origin/main `TunnelExposeCommandTest` (human create still prints full token + `cloudflared tunnel run --token`).

## Done when

- [ ] `rg 'exit\(' app/Commands` is empty
- [ ] `test ! -f app/Commands/CloudflareCommand.php`
- [ ] Every `--json` command uses **both** traits and has **no** local `getConnector`
- [ ] `vendor/bin/pest --compact` green (Agent JSON + tunnel:expose tests)

## Out of scope

- PR #6 `CloudflareCommand` / `formatApiError` / token-only connector
- Extra envelope keys (`schema_version`, `command`, `error.code`)
- Minified JSON
- Merging or deploying
