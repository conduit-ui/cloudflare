# Setup

Setup validates a Cloudflare API token against the live API and writes `CLOUDFLARE_API_TOKEN` and `CLOUDFLARE_ACCOUNT_ID` into `.env` for later commands.

## Sub-features

- `setup-noninteractive-missing` fails fast when `--non-interactive` lacks `--token` or `--account-id`.
- `setup-bad-token` rejects an invalid token without writing usable credentials.
- `setup-success` (live) verifies the token and writes isolate `.env`.

## How to get to it (user POV)

- Run `./cf setup` interactively, or
- Run `./cf setup --token=... --account-id=... --non-interactive` (agents/CI), optionally with `--json`.

## Driving it with verify-cf

Preconditions:

- `bin/verify-cf doctor` reports `cf_list=ok`.
- An isolate tree from `bin/verify-cf isolate-setup` (never the developer checkout).
- `CF_BIN` points at `$isolate/cf`.

- **Missing token (non-interactive).** Run `bin/verify-cf drive setup --non-interactive --json`. Exit non-zero. Stdout/stderr mention that the token is required.
- **Bad token.** Run `bin/verify-cf drive setup --token=cf_invalid --account-id=00000000000000000000000000000000 --non-interactive --json`. Exit non-zero. Isolate `.env` must not contain `CLOUDFLARE_API_TOKEN=cf_invalid` as a successful save (file may be absent or unchanged from empty).
- **Success (live only).** When `live_creds=present`, run `bin/verify-cf drive setup --token="$CLOUDFLARE_API_TOKEN" --account-id="$CLOUDFLARE_ACCOUNT_ID" --non-interactive --json`. Exit `0`. Stdout JSON has `"success": true` and the account id. Re-read isolate `.env` and confirm both keys are present.
- **Proof.** Retain drive artifacts plus a copy of the isolate `.env` (redact token values in any shared log; keep the file under evidence only when needed).

## Gotchas

- Success path hits the live Cloudflare verify-token API — offline CI cannot complete `setup-success`.
- Writing `.env` in the main checkout overwrites developer credentials; always isolate.
- `setup --json` success shape is `{success,message,account_id}`, not `{ok,data}`.
