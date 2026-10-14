# Agent JSON / missing credentials

When credentials are unset, agent-facing commands fail with a stable JSON envelope on stdout and exit code `1`, so Conduit agents can parse errors without scraping human tables.

## Sub-features

- `json-missing-creds` returns `{"ok":false,"error":"CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set"}` for `zones`.
- `json-exit-code` uses exit `1` on that failure (not `0`).
- `json-stdout-only` places the envelope on stdout (stderr may be empty).

## How to get to it (user POV)

- Run `./cf zones --json` with neither env var nor `.env` credentials set.
- An agent always passes `--json` (see `docs/agent-output.md`).

## Driving it with verify-cf

Preconditions:

- `bin/verify-cf doctor` reports `cf_list=ok`.
- Process env has no `CLOUDFLARE_API_TOKEN` / `CLOUDFLARE_ACCOUNT_ID` (or they are empty).
- Project `.env` either absent or those keys empty for this drive (override with `env -u` if needed).

- **Clear creds and list zones.** Run `env -u CLOUDFLARE_API_TOKEN -u CLOUDFLARE_ACCOUNT_ID CF_VERIFY_RUN_ID=$CF_VERIFY_RUN_ID .cursor/skills/verify-cf/bin/verify-cf drive zones --json`. Exit code `1`. Stdout JSON has `ok: false`, no `data` key, and `error` exactly `CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set`.
- **Proof.** Keep the `*.stdout`, `*.stderr`, and `*.meta.json` files under `artifacts/$CF_VERIFY_RUN_ID/`. Meta `exit_code` must be `1`; stdout must decode as the failure envelope above.

## Gotchas

- `setup --json` uses `{success,message}` — not this `{ok,error}` envelope. Do not mix contracts.
- A populated project `.env` can hide missing process env. Use `env -u` or an isolate with no `.env`.
- Do not treat Pest's `Artisan::call('zones')` as verification of this skill path.
