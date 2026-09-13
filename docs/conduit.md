# Using `cf` in the Conduit ecosystem

`cf` is the Cloudflare management CLI for [conduit-ui](https://github.com/conduit-ui) agent tooling. Agents should prefer **JSON output**, treat exit codes as success/failure, and keep secrets in `.env` (never in prompts or commits).

Walkthrough with sample output: [demo.md](demo.md).

## Setup

```bash
git clone https://github.com/conduit-ui/cloudflare.git
cd cloudflare
composer install
cp .env.example .env
```

Set:

| Variable | Required for | Notes |
|----------|--------------|--------|
| `CLOUDFLARE_API_TOKEN` | all commands | API token from the Cloudflare dashboard |
| `CLOUDFLARE_ACCOUNT_ID` | `tunnel:*` | Account ID (sidebar on any zone page) |

Minimum token scopes:

| Command group | Permission |
|---------------|------------|
| `zones` | Zone:Read |
| `dns:*` | DNS:Edit |
| `tunnel:*` | Cloudflare Tunnel:Edit |

## Agent conventions

1. **Always pass `--json`** when parsing results.
2. **Check exit code** — `0` success, non-zero failure (errors go to stderr / human error lines).
3. **`--json` prints the API `result`** as pretty-printed JSON on stdout. Lists are arrays. Creates are objects. Failures are a human error line and a non-zero exit.
4. **Zone args** accept a zone ID (`^[a-f0-9]{32}$`) or a domain name; domain names are resolved via the Zones API.
5. **Do not print tokens.** Read credentials from the environment only. `tunnel:create` / `tunnel:expose --json` include create-time `token` and `credentials_file` — store them, do not log them.
6. Prefer composing with sibling tools (`issue-cli`, `qdrant-tools`, `pr-cli`, …) rather than re-implementing Cloudflare calls.

```bash
./cf zones --json | jq '.[].name'
./cf tunnel:create my-app --json | jq -r '.id'
./cf tunnel:expose my-app app.example.com --json | jq -r '.tunnel.id'
```

## Typical recipes

### List zones

```bash
./cf zones --json
./cf zones --name=example.com --json
```

JSON is an array of zone objects (`id`, `name`, `status`, `plan`, …).

### Create DNS for an app

Resolve the zone, then create records. Example: proxied A record and a CNAME for `www`.

```bash
# Inspect existing records
./cf dns:list example.com --json
./cf dns:list example.com --type=A --json

# Point apex at an origin IP (proxied)
./cf dns:create example.com A @ 203.0.113.10 --proxied --json

# www → apex (proxied)
./cf dns:create example.com CNAME www example.com --proxied --json
```

`--ttl` defaults to `1` (Cloudflare auto). Omit `--proxied` for DNS-only (grey cloud).

### Create a tunnel

```bash
./cf tunnel:list --json
./cf tunnel:create my-app --json
```

Tunnel create returns a tunnel object (`id`, `name`, `status`, `token`, `credentials_file`, …). Runtime still uses `cloudflared` locally:

```bash
cloudflared tunnel route dns my-app app.example.com
cloudflared tunnel run --token <token-from-create>
```

### Expose a local app

One-shot: create the tunnel, print the connector token, and (when a hostname is given) attempt remote ingress + DNS CNAME via the API. Falls back to exact `cloudflared` commands when the API cannot route.

```bash
./cf tunnel:expose my-app
./cf tunnel:expose my-app app.example.com http://localhost:3000 --json
```

Default local URL is `http://localhost:8000`. Then:

```bash
cloudflared tunnel run --token <token-from-output>
```

Delete when done:

```bash
./cf tunnel:delete <tunnel-id> --force
```

## Command map

| Intent | Command |
|--------|---------|
| List zones | `./cf zones [--name=…] [--json]` |
| List DNS | `./cf dns:list <zone> [--type=…] [--name=…] [--json]` |
| Create DNS | `./cf dns:create <zone> <type> <name> <content> [--proxied] [--ttl=1] [--json]` |
| List tunnels | `./cf tunnel:list [--json]` |
| Create tunnel | `./cf tunnel:create <name> [--json]` |
| Expose local app | `./cf tunnel:expose <name> [hostname] [url] [--json]` |
| Delete tunnel | `./cf tunnel:delete <id> [--force]` |

## Related Conduit tools

- [issue-cli](https://github.com/conduit-ui/issue-cli) — GitHub issues for agents
- [qdrant-tools](https://github.com/conduit-ui/qdrant-tools) — Qdrant vector DB CLI
- [pr-cli](https://github.com/conduit-ui/pr-cli) — pull request workflows
- [commit-cli](https://github.com/conduit-ui/commit-cli) — conventional / AI-assisted commits
- [knowledge](https://github.com/conduit-ui/knowledge) — semantic knowledge base

See also [AGENTS.md](../AGENTS.md) for coding-agent guidance in this repo.
