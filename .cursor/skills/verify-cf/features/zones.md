# Zones

Zones lists Cloudflare zones for the configured account so a user can see which domains are manageable.

## Sub-features

- `zones-json` returns `{ok:true,data:[...]}` with zone objects.
- `zones-human` prints a table when `--json` is omitted (secondary; prefer JSON for agents).

## How to get to it (user POV)

- Run `./cf zones` or `./cf zones --json` after credentials are configured.

## Driving it with verify-cf

Preconditions:

- `bin/verify-cf doctor` reports `live_creds=present` and preferably `zones_json=ok`.
- Process env provides `CLOUDFLARE_API_TOKEN` and `CLOUDFLARE_ACCOUNT_ID`.

- **List zones as JSON.** Run `bin/verify-cf drive zones --json`. Exit `0`. Stdout JSON has `ok: true` and `data` as an array (may be empty for an account with no zones).
- **Proof.** Artifact stdout decodes; if the account has zones, at least one element includes `id` and `name`. Empty `data: []` with `ok: true` is a valid proof for an empty account — record that in the run notes.

## Gotchas

- Without live credentials, report unmet precondition — do not substitute Pest mocks.
- Token needs Zone:Read (see README permission table).
- Filter flag `--name=` exists; baseline journey is the unfiltered list.
