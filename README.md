# Cloudflare CLI

Lightweight Cloudflare management CLI built with Laravel Zero and Saloon.

## Installation

```bash
git clone https://github.com/conduit-ui/cloudflare.git
cd cloudflare
composer install
cp .env.example .env
```

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

## Commands

### Zones
```bash
./cf zones                    # List all zones
./cf zones --json             # JSON output
```

### DNS Records
```bash
./cf dns:list <zone>                        # List records (zone ID or domain)
./cf dns:list jordanpartridge.us --type=A   # Filter by type
./cf dns:create <zone> A api 1.2.3.4        # Create A record
./cf dns:create <zone> CNAME www example.com --proxied
```

### Tunnels
```bash
./cf tunnel:list              # List all tunnels
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

`tunnel:create` masks secrets in the summary table and prints full token / credentials once. Use `--json` for the raw API result.

## Architecture

```
app/
├── Commands/                    # CLI commands
├── Integrations/Cloudflare/
│   ├── CloudflareConnector.php  # Saloon connector
│   ├── Resources/               # API resource classes
│   └── Requests/                # Individual API requests
```

## License

MIT
