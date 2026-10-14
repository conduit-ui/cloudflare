# Tunnel list

Tunnel list shows Cloudflare Tunnels for the account.

## Sub-features

- `tunnel-list-json` returns `{ok:true,data:[...]}`.

## How to get to it (user POV)

- Run `./cf tunnel:list` or `./cf tunnel:list --json` after credentials are configured.

## Driving it with verify-cf

Preconditions:

- `live_creds=present`.
- Token permits Cloudflare Tunnel read/edit as documented in the README.

- **List tunnels.** Run `bin/verify-cf drive tunnel:list --json`. Exit `0`. Stdout JSON has `ok: true` and `data` as an array (may be empty).
- **Proof.** Retain artifacts; empty list with `ok: true` is valid for accounts with no tunnels.

## Gotchas

- `tunnel:create` / `tunnel:expose` / `tunnel:delete` mutate remote state and can print secrets — not covered here; clean up any tunnels you create in other journeys.
- Without live credentials, report unmet precondition.
