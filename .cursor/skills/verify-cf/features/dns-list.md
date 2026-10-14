# DNS list

DNS list shows DNS records for a zone identified by zone ID or domain name.

## Sub-features

- `dns-list-json` returns `{ok:true,data:[...]}` for a zone.
- `dns-list-type-filter` narrows results with `--type=`.

## How to get to it (user POV)

- Run `./cf dns:list <zone>` or `./cf dns:list <zone> --json`.
- Optionally add `--type=A` / `--name=...`.

## Driving it with verify-cf

Preconditions:

- `live_creds=present`.
- A known zone name or ID from a prior `zones --json` drive (store it as `ZONE` for the run). If the account has zero zones, report unmet precondition — do not invent a zone.

- **List records.** Run `bin/verify-cf drive dns:list "$ZONE" --json`. Exit `0`. Stdout JSON has `ok: true` and `data` as an array.
- **Optional type filter.** Run `bin/verify-cf drive dns:list "$ZONE" --type=A --json`. Exit `0`. Every returned record's `type` is `A` when `data` is non-empty.
- **Proof.** Keep both artifact sets; note `$ZONE` in the run.

## Gotchas

- Creating/updating/deleting DNS is out of this feature file — use dedicated journeys and clean up remote records.
- Zone argument accepts ID or domain; prefer the domain string from `zones` for readability.
- DNS:Edit is required for mutations; list needs read access to DNS for that zone.
