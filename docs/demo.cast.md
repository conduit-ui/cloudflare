# Sample terminal session (`demo.cast`)

Fake/sample output for docs and recordings. No live Cloudflare API calls required.

To turn this into a real [asciinema](https://asciinema.org/) cast later:

```bash
# asciinema rec docs/demo.cast
# …run the commands from demo.md…
# asciinema upload docs/demo.cast
```

Until a binary `.cast` is checked in, use the transcript below as the expected session.

`--json` prints pretty-printed JSON of the API `result`. Lists are arrays. Creates are objects. `tunnel:expose --json` is an object with `tunnel`, `hostname`, `url`, `ingress_configured`, `dns_created`, `dns_record`, `configuration`, and `dns_route_command`.

---

```text
$ ./cf zones
+----------------------------------+-------------------+--------+----------+
| ID                               | Name              | Status | Plan     |
+----------------------------------+-------------------+--------+----------+
| a1b2c3d4e5f6789012345678abcdef01 | example.com       | active | Free     |
| 9f8e7d6c5b4a3210987654321fedcba0 | staging.example   | active | Pro      |
+----------------------------------+-------------------+--------+----------+

$ ./cf zones --json
[
    {
        "id": "a1b2c3d4e5f6789012345678abcdef01",
        "name": "example.com",
        "status": "active",
        "plan": {
            "name": "Free Website"
        }
    }
]

$ ./cf dns:list example.com --type=A
+------+------------------+-------------+---------+------+
| Type | Name             | Content     | Proxied | TTL  |
+------+------------------+-------------+---------+------+
| A    | example.com      | 203.0.113.1 | Yes     | Auto |
| A    | api.example.com  | 203.0.113.2 | No      | Auto |
+------+------------------+-------------+---------+------+

$ ./cf dns:create example.com A demo-cf 203.0.113.10
DNS record created successfully!
+---------+----------------------------------+
| Field   | Value                            |
+---------+----------------------------------+
| ID      | rec01abcdefghijklmnopqrstuvwxyz |
| Type    | A                                |
| Name    | demo-cf.example.com              |
| Content | 203.0.113.10                     |
| Proxied | No                               |
+---------+----------------------------------+

$ ./cf tunnel:expose demo-conduit
Creating tunnel "demo-conduit" to expose http://localhost:8000...

Tunnel ready to expose your app!
+---------+--------------------------------------+
| Field   | Value                                |
+---------+--------------------------------------+
| ID      | 11111111-2222-3333-4444-555555555555 |
| Name    | demo-conduit                         |
| Status  | inactive                             |
| Created | 2026-03-26T12:00:00Z                 |
| Token   | eyJhbG****************demo           |
+---------+--------------------------------------+

Expose your local app:
  1. Install cloudflared: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/
  2. Run: cloudflared tunnel run --token <token>
  3. Local service for ingress: http://localhost:8000
  4. Optional DNS: cloudflared tunnel route dns demo-conduit <hostname>

$ ./cf tunnel:expose demo-conduit demo-tunnel.example.com http://localhost:3000 --json
{
    "tunnel": {
        "id": "11111111-2222-3333-4444-555555555555",
        "name": "demo-conduit",
        "status": "inactive",
        "created_at": "2026-03-26T12:00:00Z",
        "token": "<redacted>",
        "credentials_file": {
            "AccountTag": "account-tag",
            "TunnelID": "11111111-2222-3333-4444-555555555555",
            "TunnelName": "demo-conduit",
            "TunnelSecret": "<redacted>"
        }
    },
    "hostname": "demo-tunnel.example.com",
    "url": "http://localhost:3000",
    "ingress_configured": true,
    "dns_created": true,
    "dns_record": {
        "id": "rec02abcdefghijklmnopqrstuvwxyz",
        "type": "CNAME",
        "name": "demo-tunnel.example.com",
        "content": "11111111-2222-3333-4444-555555555555.cfargotunnel.com"
    },
    "configuration": {},
    "dns_route_command": "cloudflared tunnel route dns demo-conduit demo-tunnel.example.com"
}

$ ./cf tunnel:create demo-conduit --json
{
    "id": "11111111-2222-3333-4444-555555555555",
    "name": "demo-conduit",
    "status": "inactive",
    "created_at": "2026-03-26T12:00:00Z",
    "token": "<redacted>",
    "credentials_file": {
        "AccountTag": "account-tag",
        "TunnelID": "11111111-2222-3333-4444-555555555555",
        "TunnelName": "demo-conduit",
        "TunnelSecret": "<redacted>"
    }
}

$ ./cf tunnel:list --json
[
    {
        "id": "11111111-2222-3333-4444-555555555555",
        "name": "demo-conduit",
        "status": "inactive",
        "created_at": "2026-03-26T12:00:00Z",
        "connections": []
    }
]

$ ./cf tunnel:delete 11111111-2222-3333-4444-555555555555 --force
Tunnel deleted successfully.
```

---

**Agent tip:** pipe any of these with `--json` into `jq` for Conduit workflows:

```bash
./cf zones --json | jq '.[].name'
./cf tunnel:create demo-conduit --json | jq -r '.id'
./cf tunnel:expose demo-conduit demo-tunnel.example.com --json | jq -r '.tunnel.id'
```
