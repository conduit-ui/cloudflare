# Agent JSON output contract

Conduit agents should pass `--json` on Cloudflare CLI commands and parse a stable envelope from **stdout**.

## Envelope

| Outcome | Exit code | stdout JSON |
|---------|-----------|-------------|
| Success | `0` (`SUCCESS`) | `{"ok":true,"data":...}` |
| Failure | `1` (`FAILURE`) | `{"ok":false,"error":"..."}` |

- Pretty-printed JSON (`JSON_PRETTY_PRINT`), unescaped slashes.
- Both envelopes go to **stdout** (not stderr).
- `data` is the Cloudflare API `result` payload (array or object), or a small command-specific object (e.g. tunnel delete / expose).
- `error` is a human-readable string (may include the API response body).
- Missing token **or** account id: failure envelope, exit `1`, `error` is exactly `CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set`. `getConnector()` prints that once, returns `null`, and does not `exit()`.

## Commands

| Command | `--json` | `data` on success |
|---------|----------|-------------------|
| `zones` | yes | zone list |
| `dns:list` | yes | DNS record list |
| `dns:create` | yes | created record |
| `tunnel:list` | yes | tunnel list |
| `tunnel:create` | yes | created tunnel (includes create-time `token` / `credentials_file` when the API returns them) |
| `tunnel:delete` | yes | `{ "deleted": true, "id": "..." }` — or `{ "deleted": false, "cancelled": true }` (exit `0`) when confirmation is declined |
| `tunnel:expose` | yes | `{ tunnel, hostname, url, ingress_configured, dns_created, dns_record, configuration, dns_route_command }` |

Human (non-JSON) `tunnel:create` and `tunnel:expose` still print the create-time token and cloudflared run steps.

## Agent checklist

1. Always pass `--json`.
2. Check exit code first.
3. Decode stdout as JSON; require `ok` boolean.
4. On `ok: true`, use `data`. On `ok: false`, surface `error`.
5. Do not scrape human table/info output.

## Implementation

- Envelopes: `App\Commands\Concerns\OutputsJson` (`jsonSuccess()`, `jsonFail()`, `wantsJson()`).
- Credentials: `App\Commands\Concerns\InteractsWithCloudflare::getConnector()` prints the missing-creds error once, returns `?CloudflareConnector`, and never exits. Commands return `FAILURE` when it is `null`.
- There is no `CloudflareCommand` base class.
