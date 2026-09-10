# Agent JSON output contract

Conduit agents should pass `--json` on Cloudflare CLI commands and parse a stable envelope from **stdout**.

## Envelope

| Outcome | Exit code | stdout JSON |
|---------|-----------|-------------|
| Success | `0` (`SUCCESS`) | `{"ok":true,"data":...}` |
| Failure | `1` (`FAILURE`) | `{"ok":false,"error":"..."}` |

- Pretty-printed JSON (`JSON_PRETTY_PRINT`), unescaped slashes.
- `data` is the Cloudflare API `result` payload (array or object), or a small command-specific object (e.g. tunnel delete).
- `error` is a human-readable string (may include the API response body).

## Commands

| Command | `--json` | `data` on success |
|---------|----------|-------------------|
| `zones` | yes | zone list |
| `dns:list` | yes | DNS record list |
| `dns:create` | yes | created record |
| `tunnel:list` | yes | tunnel list |
| `tunnel:create` | yes | created tunnel |
| `tunnel:delete` | yes | `{ "deleted": true, "id": "..." }` (or cancelled) |

## Agent checklist

1. Always pass `--json`.
2. Check exit code first.
3. Decode stdout as JSON; require `ok` boolean.
4. On `ok: true`, use `data`. On `ok: false`, surface `error`.
5. Do not scrape human table/info output.

## Implementation

Shared helpers live in `App\Commands\Concerns\OutputsJson` (`jsonSuccess()`, `jsonFail()`, `wantsJson()`).
