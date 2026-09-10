# Conduit Cloudflare CLI (`cf`)

**Zones, DNS, and tunnels for Conduit agents — a Laravel Zero + Saloon CLI that turns Cloudflare into a scriptable, `--json`-ready toolbox.**

[![CI](https://img.shields.io/github/actions/workflow/status/conduit-ui/cloudflare/ci.yml?branch=main&style=flat-square)](https://github.com/conduit-ui/cloudflare/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/github/license/conduit-ui/cloudflare?style=flat-square)](LICENSE)
[![Release](https://img.shields.io/github/v/release/conduit-ui/cloudflare?style=flat-square&include_prereleases&sort=semver)](https://github.com/conduit-ui/cloudflare/releases)

Part of the [Conduit](https://github.com/conduit-ui) agent ecosystem: agents and humans share the same `cf` surface for listing zones, managing DNS, and provisioning Cloudflare Tunnels.

---

## Quick install

```bash
git clone https://github.com/conduit-ui/cloudflare.git
cd cloudflare
composer install
cp .env.example .env
# Edit .env with your Cloudflare credentials
./cf zones
```

Composer registers the `cf` binary. When a PHAR or global Composer package is published, install with:

```bash
# PHAR (when available)
# curl -L https://github.com/conduit-ui/cloudflare/releases/latest/download/cf.phar -o cf
# chmod +x cf && sudo mv cf /usr/local/bin/cf

# Global Composer (when published on Packagist)
# composer global require conduit-ui/cloudflare
```

Build a local PHAR with [Box](https://github.com/box-project/box) using the included `box.json` when you want a single-file binary.

---

## Configuration

Edit `.env`:

```bash
CLOUDFLARE_API_TOKEN=your_token_here
CLOUDFLARE_ACCOUNT_ID=your_account_id
```

| Variable | Where to get it |
|----------|-----------------|
| `CLOUDFLARE_API_TOKEN` | [API Tokens](https://dash.cloudflare.com/profile/api-tokens) |
| `CLOUDFLARE_ACCOUNT_ID` | Right sidebar on any zone overview in the dashboard |

### Token permissions

Create a custom token with the minimum scopes your workflow needs:

| Command | Required permission |
|---------|---------------------|
| `zones` | **Zone → Zone → Read** |
| `dns:list`, `dns:create` | **Zone → DNS → Edit** |
| `tunnel:list`, `tunnel:create`, `tunnel:expose`, `tunnel:delete` | **Account → Cloudflare Tunnel → Edit** |

`tunnel:*` also requires `CLOUDFLARE_ACCOUNT_ID` to be set.

---

## Agent-friendly `--json`

Every read/write command that returns data accepts `--json`. Agents and scripts get structured stdout instead of tables — ideal for Conduit pipelines and shell glue.

```bash
./cf zones --json
./cf dns:list example.com --type=A --json
./cf tunnel:list --json
./cf tunnel:create my-app --json
```

Human-readable tables remain the default when `--json` is omitted.

---

## Command reference

### Zones

```bash
./cf zones                         # List all zones
./cf zones --name=example.com      # Filter by zone name
./cf zones --json                  # Machine-readable output
```

### DNS records

Zone argument accepts a **zone ID** (32-char hex) or a **domain name** (resolved via the Zones API).

```bash
./cf dns:list <zone>                               # List records
./cf dns:list example.com --type=A                 # Filter by type
./cf dns:list example.com --name=api               # Filter by name
./cf dns:list example.com --json

./cf dns:create <zone> A api 1.2.3.4               # Create A record
./cf dns:create <zone> CNAME www example.com --proxied
./cf dns:create <zone> TXT _verify "token=abc" --ttl=3600
./cf dns:create <zone> A api 1.2.3.4 --json
```

| Option | Description |
|--------|-------------|
| `--type=` | Filter list by record type (`A`, `CNAME`, `TXT`, …) |
| `--name=` | Filter list by record name |
| `--proxied` | Enable Cloudflare proxy on create |
| `--ttl=1` | TTL in seconds (`1` = auto) |
| `--json` | JSON output |

### Tunnels

```bash
./cf tunnel:list                   # List tunnels
./cf tunnel:list --json

./cf tunnel:create <name>          # Create a tunnel (prints token/credentials)
./cf tunnel:create my-app --json

./cf tunnel:expose <name> [hostname] [url]  # Create + route + cloudflared steps
./cf tunnel:expose my-app --json

./cf tunnel:delete <id>            # Delete (prompts for confirmation)
./cf tunnel:delete <id> --force    # Skip confirmation
```

`tunnel:create` masks secrets in the summary table and prints full token / credentials once. Use `--json` for the raw API result.

### Expose a local app

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

---

## Architecture

Laravel Zero drives the CLI; Saloon models the Cloudflare HTTP API.

```
app/
├── Commands/                      # zones, dns:*, tunnel:*
├── Integrations/Cloudflare/
│   ├── CloudflareConnector.php    # Saloon connector + auth
│   ├── Resources/                 # Zone, Dns, Tunnel facades
│   └── Requests/                  # One class per API call
│       ├── Zones/
│       ├── Dns/
│       └── Tunnels/
└── Providers/
```

Commands resolve credentials from `.env`, call resource methods on `CloudflareConnector`, and format either a console table or pretty-printed JSON.

---

## Demo walkthrough

Follow a scripted zones → DNS → tunnel session in [`docs/demo.md`](docs/demo.md). Sample terminal output (asciinema-style) lives in [`docs/demo.cast.md`](docs/demo.cast.md).

---

## Development

```bash
composer install
vendor/bin/pest          # Test suite
vendor/bin/pint          # Code style (Laravel Pint)
./cf list                # List registered commands
```

Requires **PHP 8.3+** with `mbstring` and `xml`.

---

## License

[MIT](LICENSE)
