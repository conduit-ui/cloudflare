# SPEC: dns:delete, dns:update, tunnel:get

Status: LOCKED

**Story:** `./cf` said DNS and tunnels were covered. On `main` they are not: `DeleteDnsRecord` and `GetTunnel` exist in Saloon with resource methods and no commands; there is no `UpdateDnsRecord` at all. Humans and agents cannot overwrite or delete a record, or inspect one tunnel, without curling `api.cloudflare.com`.

PR: https://github.com/conduit-ui/cloudflare/pull/8

## Locked

- Three Laravel Zero commands, autoloaded from `app/Commands` (do not edit `config/commands.php`).
- New Saloon request only: `UpdateDnsRecord` as **PUT** overwrite
  `PUT /zones/{zoneId}/dns_records/{recordId}`
  body = `CreateDnsRecord`: `type`, `name`, `content`, `proxied`, `ttl`.
  Docs: https://developers.cloudflare.com/api/resources/dns/subresources/records/methods/update/
- Reuse existing `DeleteDnsRecord` (`DELETE` same path) and `GetTunnel`
  (`GET /accounts/{accountId}/cfd_tunnel/{tunnelId}`).
- Wire `DnsResource::update(...)`. `delete` / `TunnelResource::get` already exist on `main`.
- Signatures (match README lines this PR adds):

```
./cf dns:update <zone> <id> <type> <name> <content> [--proxied] [--ttl=1] [--json]
./cf dns:delete <zone> <id> [--force] [--json]
./cf tunnel:get <id> [--json]
```

- Zone arg: 32 hex chars = ID; otherwise resolve via `zones()->list($name)` like `dns:create`.
- `dns:delete` confirms unless `--force`. Decline → exit 0, `Cancelled.` (`tunnel:delete`).
- DNS commands copy `DnsCreateCommand` connector (token only). Do not put them on `InteractsWithCloudflare` (that trait requires `CLOUDFLARE_ACCOUNT_ID`).
- `tunnel:get` uses `InteractsWithCloudflare` like the other tunnel commands.
- `--json` prints the Cloudflare API **`result`** with `JSON_PRETTY_PRINT`. Same as `dns:create` / `tunnel:list` on `main`.
- Human mode: success table / info line; API failure → `error(...)` + exit 1.
- README: add those command lines under DNS / Tunnels. **Keep the entire `## Expose a local app` section** (heading, examples, cloudflared token + `route dns` fallback). Do not rewrite `tunnel:expose`.
- Tests: Pest + Saloon `MockClient`, same style as `tests/Feature/TunnelExposeCommandTest.php`.
- You own (build): `app/Commands/DnsDeleteCommand.php`, `DnsUpdateCommand.php`, `TunnelGetCommand.php`, `app/Integrations/Cloudflare/Requests/Dns/UpdateDnsRecord.php`, `DnsResource::update`.
- You own (test): `tests/Feature/DnsTunnelCrudCommandsTest.php` plus a README expose-section assertion if missing.
- Research owns: `specs/`, `~/.grok/tmp/8-notes.md`.

## Done when

- [ ] `./cf list` shows `dns:delete`, `dns:update`, `tunnel:get`
- [ ] `vendor/bin/pest --compact tests/Feature/DnsTunnelCrudCommandsTest.php tests/Feature/TunnelExposeCommandTest.php` is green
- [ ] A test asserts `UpdateDnsRecord` is `Method::PUT`, endpoint `/zones/{z}/dns_records/{id}`, body keys `type` `name` `content` `proxied` `ttl`
- [ ] `--json` success output is the API `result` object (record id / tunnel id present) and does **not** contain top-level `"ok"` / `"data"` / `"error"`
- [ ] `README.md` still contains `## Expose a local app` and `./cf tunnel:expose my-app`
- [ ] `./bin/ci` is green
- [ ] This PR is **not** merged from this clone

## Out of scope

- PR #7 agent JSON envelope: `OutputsJson`, `docs/agent-output.md`, `{ok, data|error}`, wrapping existing commands. Optional `--json` **raw result** is this PR; the envelope is #7.
- `dns:upsert` (issue 14), `--dry-run` (15), `tunnel:status` / `--wait` (16)
- PATCH partial update (`/api/.../records/methods/edit/`)
- MX `--priority`, CAA `data` maps, comments/tags (create does not have them)
- Dedicated tunnel connections endpoint (`connections` is deprecated on GET tunnel; keep the same `count(connections ?? [])` as `tunnel:list`)
- Refactoring every command onto one trait / splitting `InteractsWithCloudflare`
- Touching `tunnel:expose` behavior, composer.lock, CI workflows
- Merge or deploy
