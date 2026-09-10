# Demo walkthrough: zones → DNS → tunnel

A short scripted session you can run against a real Cloudflare account, or follow while recording a terminal demo (see [`demo.cast.md`](demo.cast.md) for sample output).

**Prerequisites**

- PHP 8.3+, Composer
- Cloudflare API token with Zone:Read, DNS:Edit, and Cloudflare Tunnel:Edit
- Account ID set for tunnel commands
- Optional: [`cloudflared`](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/) for post-create routing

Replace `example.com` with a zone you own.

---

## 0. Bootstrap

```bash
git clone https://github.com/conduit-ui/cloudflare.git
cd cloudflare
composer install
cp .env.example .env
```

Edit `.env`:

```bash
CLOUDFLARE_API_TOKEN=...
CLOUDFLARE_ACCOUNT_ID=...
```

Confirm the binary:

```bash
./cf list
```

---

## 1. List zones

Human-readable table:

```bash
./cf zones
```

Filter and JSON (agent-friendly):

```bash
./cf zones --name=example.com
./cf zones --json | head
```

Note a zone ID or domain for the next steps.

---

## 2. Inspect DNS

```bash
./cf dns:list example.com
./cf dns:list example.com --type=A
./cf dns:list example.com --json
```

Create a non-production record (use a safe subdomain):

```bash
./cf dns:create example.com A demo-cf 203.0.113.10
./cf dns:create example.com CNAME demo-www example.com --proxied
./cf dns:list example.com --name=demo-cf
```

Clean up in the dashboard (or a future `dns:delete` command) when finished recording.

---

## 3. Provision a tunnel

```bash
./cf tunnel:list
./cf tunnel:create demo-conduit --json
./cf tunnel:list
```

Capture the tunnel `id` from create output. Optional `cloudflared` follow-up:

```bash
# cloudflared tunnel route dns demo-conduit demo-tunnel.example.com
# cloudflared tunnel run demo-conduit
```

When done with the demo tunnel:

```bash
./cf tunnel:delete <tunnel-id> --force
```

---

## Recording tips

1. Use a throwaway subdomain and tunnel name (`demo-*`).
2. Prefer `--json` for one clip and table output for another so both modes show.
3. Mask or redact tokens if your shell history or env dumps appear on screen.
4. Keep the session under ~2 minutes: zones → dns:list → dns:create → tunnel:create → tunnel:delete.

For expected sample output without live API calls, see [`demo.cast.md`](demo.cast.md).
