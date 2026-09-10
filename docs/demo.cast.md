# Sample terminal session (`demo.cast`)

Fake/sample output for docs and recordings. No live Cloudflare API calls required.

To turn this into a real [asciinema](https://asciinema.org/) cast later:

```bash
# asciinema rec docs/demo.cast
# …run the commands from demo.md…
# asciinema upload docs/demo.cast
```

Until a binary `.cast` is checked in, use the transcript below as the expected session.

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

$ ./cf tunnel:create demo-conduit
Creating tunnel: demo-conduit...

Tunnel created successfully!
+---------+--------------------------------------+
| Field   | Value                                |
+---------+--------------------------------------+
| ID      | 11111111-2222-3333-4444-555555555555 |
| Name    | demo-conduit                         |
| Status  | inactive                             |
| Created | 2026-03-26T12:00:00Z                 |
+---------+--------------------------------------+

Next steps:
  1. Configure ingress rules in ~/.cloudflared/config.yml
  2. Run: cloudflared tunnel route dns demo-conduit <hostname>
  3. Start tunnel: cloudflared tunnel run demo-conduit

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
```
