# Cloudflare CLI

Lightweight Cloudflare management CLI built with Laravel Zero and Saloon.

## Installation

### Binary (recommended)

Download `cf` from [Releases](https://github.com/conduit-ui/cloudflare/releases), then:

```bash
chmod +x cf
sudo mv cf /usr/local/bin/cf
```

Requires PHP 8.3+ (zlib). Export `CLOUDFLARE_API_TOKEN` and `CLOUDFLARE_ACCOUNT_ID` before running commands.

### Composer

```bash
composer require conduit-ui/cloudflare
# or globally:
composer global require conduit-ui/cloudflare
```

### From source

```bash
git clone https://github.com/conduit-ui/cloudflare.git
cd cloudflare
composer install
cp .env.example .env
```

Build a local PHAR after `composer install` with `composer build` (or `php cf app:build cf`). Artifact: `builds/cf`.

## Configuration

Edit `.env`:
```bash
CLOUDFLARE_API_TOKEN=your_token_here
CLOUDFLARE_ACCOUNT_ID=your_account_id
```

Get credentials from:
- **API Token**: https://dash.cloudflare.com/profile/api-tokens
- **Account ID**: Right sidebar on any zone page

### Token Permissions

| Command | Required Permission |
|---------|---------------------|
| `zones` | Zone:Read |
| `dns:*` | DNS:Edit |
| `tunnel:*` | Cloudflare Tunnel:Edit |

## Agent JSON output

Pass `--json` for a stable envelope on stdout: `{"ok":true,"data":...}` on success, `{"ok":false,"error":"..."}` on failure (exit `0` / `1`). See [docs/agent-output.md](docs/agent-output.md).

## Commands

### Zones
```bash
./cf zones                    # List all zones
./cf zones --json             # Agent JSON envelope
```

### DNS Records
```bash
./cf dns:list <zone>                        # List records (zone ID or domain)
./cf dns:list jordanpartridge.us --type=A   # Filter by type
./cf dns:create <zone> A api 1.2.3.4        # Create A record
./cf dns:create <zone> CNAME www example.com --proxied
./cf dns:update <zone> <id> A api 5.6.7.8   # Update record (PUT)
./cf dns:update <zone> <id> A api 5.6.7.8 --proxied --ttl=300 --json
./cf dns:delete <zone> <id>                 # Delete record (prompts)
./cf dns:delete <zone> <id> --force --json  # Skip confirmation
```

### Tunnels
```bash
./cf tunnel:list              # List all tunnels
./cf tunnel:get <id>          # Get tunnel details
./cf tunnel:get <id> --json
./cf tunnel:create <name>     # Create tunnel (prints token/credentials)
./cf tunnel:expose <name> [hostname] [url]  # Create + route + cloudflared steps
./cf tunnel:delete <id>       # Delete tunnel
```

## Expose a local app

One guided flow: create a tunnel, print the connector token, and (when a hostname is given) attempt remote ingress + DNS CNAME via the API. Falls back to exact `cloudflared` commands when the API cannot route.

```bash
# Default local service: http://localhost:8000
./cf tunnel:expose my-app

# Hostname + custom local URL
./cf tunnel:expose my-app app.example.com http://localhost:3000

# Full create payload (token, credentials_file, routing results)
./cf tunnel:expose my-app app.example.com --json
```

Then run [cloudflared](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/) with the printed token:

```bash
cloudflared tunnel run --token <token-from-output>
```

If DNS was not created via API:

```bash
cloudflared tunnel route dns my-app app.example.com
```

`tunnel:create` masks secrets in the summary table and prints full token / credentials once. Use `--json` for the agent envelope (`{"ok":true,"data":...}`).

## Architecture

```
app/
├── Commands/                    # CLI commands
├── Integrations/Cloudflare/
│   ├── CloudflareConnector.php  # Saloon connector
│   ├── Resources/               # API resource classes
│   └── Requests/                # Individual API requests
```

## Development

PR quality is [quality-runners](https://github.com/synapse-sentinel/quality-runners) (Pint, Pest, PHPStan, Rector on the clone host). Until that runner is subscribed to this repo, GitHub Actions still runs Pint `--test` and Pest as a residual. Local `./bin/ci` is the full set.

Locally:

```bash
composer install
./bin/ci
```

## License

MIT
