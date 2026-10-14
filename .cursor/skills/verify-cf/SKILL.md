---
name: verify-cf
description: "Drive the Cloudflare CLI (`cf`) the way a user/agent does — launch deps, doctor health, run real commands with `--json`, and capture exit/stdout/stderr proof. Use when verifying conduit-ui/cloudflare behavior, agent JSON envelopes, setup, zones, DNS, or tunnels."
---

# verify-cf

Project-local verification for the **Cloudflare management CLI** (`./cf`). Primary surface is the terminal. There is no local HTTP server, database, or long-lived daemon. Cloudflare's HTTP API is the production boundary; live network calls need `CLOUDFLARE_API_TOKEN` and `CLOUDFLARE_ACCOUNT_ID`. Without credentials, only missing-creds / setup-validation journeys are driveable against the real binary.

Helper (always prefer this over ad-hoc paths):

```bash
.cursor/skills/verify-cf/bin/verify-cf <launch|doctor|drive|isolate-setup|cleanup|evidence-dir>
```

Evidence root: `.cursor/skills/verify-cf/artifacts/<run-id>/` (survives cleanup). Set `CF_VERIFY_RUN_ID` to pin a run id across commands.

## Launch

No server to keep alive. Launch means: PHP 8.3+, Composer deps installed, `./cf` executable and advertising commands.

```bash
export CF_VERIFY_RUN_ID="${CF_VERIFY_RUN_ID:-$(date +%Y%m%dT%H%M%S)-$$}"
.cursor/skills/verify-cf/bin/verify-cf launch
# ready marker: "verify-cf ready repo=... run=... evidence=..."
```

Teardown for isolate trees only:

```bash
.cursor/skills/verify-cf/bin/verify-cf cleanup
```

Cleanup never deletes evidence under `artifacts/`.

## Doctor

Read-only. Run first whenever anything looks off:

```bash
.cursor/skills/verify-cf/bin/verify-cf doctor
```

Worth driving only when exit `0` and output includes `vendor=ok`, `entrypoint=ok`, `cf_list=ok`. `live_creds=present` means zones/DNS/tunnel live journeys may proceed; `live_creds=absent` means stick to missing-creds / setup failure journeys (or Pest mocks for offline CI — not a substitute for a mapped live path).

## Drive

Harness: the real `./cf` process via the helper. Always pass `--json` for agent-facing commands (see `docs/agent-output.md`). Prefer process env for credentials over writing the project `.env`.

```bash
.cursor/skills/verify-cf/bin/verify-cf drive zones --json
# prints: exit=N stdout=... stderr=... meta=...
```

For `setup` (writes `.env`), **never** use the developer's checkout `.env`. Create an isolate first:

```bash
ISOLATE="$(.cursor/skills/verify-cf/bin/verify-cf isolate-setup)"
# or: export CF_BIN="$(cat artifacts/<run>/isolate_home.txt)/cf" after isolate-setup
export CF_BIN="$(.cursor/skills/verify-cf/bin/verify-cf isolate-setup | sed -n 's/^isolate=//p')/cf"
.cursor/skills/verify-cf/bin/verify-cf drive setup --token=bad --account-id=bad --non-interactive --json
```

Stable handles: command names (`zones`, `dns:list`, `setup`), flags (`--json`, `--non-interactive`, `--force`), and JSON keys (`ok`, `data`, `error`). Do not scrape human tables when `--json` is available.

`setup --json` uses a different success shape (`success` / `message` / `account_id`), not `{ok,data}` — see feature map.

## Evidence

Proof lives under `.cursor/skills/verify-cf/artifacts/<run-id>/`:

| File | Contents |
|------|----------|
| `*.stdout` | Full stdout (JSON envelopes) |
| `*.stderr` | Full stderr |
| `*.meta.json` | `exit_code` + argv |

Proof standards:

1. Exercise the real `./cf` user path (not `Artisan::call` inside Pest) for skill-driven proofs.
2. Capture the command (meta argv), the exit code, and the resulting stdout/stderr — not only a summary.
3. For mutations (DNS/tunnel create/delete, successful setup), follow with a second read (`dns:list`, `zones`, or re-read isolate `.env`) that shows the side effect.
4. Mocks belong in Pest (`composer test`) at the Saloon/Cloudflare HTTP boundary. Do not mock inside a verify-cf drive. Live API failures with valid-looking creds are real failures.
5. When `live_creds=absent`, report live journeys as unmet preconditions — do not weaken them to a mocked Pest test and call them verified.

## Cleanup

```bash
.cursor/skills/verify-cf/bin/verify-cf cleanup
```

Removes only `/tmp/cf-verify-$CF_VERIFY_RUN_ID` (or `CF_VERIFY_HOME`) created by `isolate-setup`. Does not kill by process name (nothing long-lived). Does not delete `artifacts/`.

## Helpers

| Invocation | Purpose |
|------------|---------|
| `bin/verify-cf launch` | Install deps if needed; assert `cf list` usable |
| `bin/verify-cf doctor` | Read-only health |
| `bin/verify-cf drive <args...>` | Run `./cf` + capture evidence |
| `bin/verify-cf isolate-setup` | Disposable tree + vendor symlink for setup |
| `bin/verify-cf evidence-dir` | Print current evidence directory |
| `bin/verify-cf cleanup` | Drop isolate home; keep evidence |

## Feature map

See [`features/README.md`](features/README.md). Drive the mapped entry points there; a proof that uses one convenient command is incomplete when the map lists others for that feature.
