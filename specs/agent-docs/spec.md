# SPEC: Honest agent docs for `cf`

Status: LOCKED

**Story:** PR #5 (`638d263`) said `--json` is a stable `{"ok":true,"data":...}` envelope and linked `docs/agent-output.md`. On this branch and on `main`, `--json` prints pretty-printed Cloudflare `result` (or the `tunnel:expose` command object). `docs/agent-output.md` exists only on unmerged PR #7. A second README hero (PR #4) must not be restyled here; fold that PR's demo files instead.

## Locked

- This crew owns `AGENTS.md`, `docs/conduit.md`, `docs/demo.md`, `docs/demo.cast.md`, README **ecosystem + honest `--json` only**. Research owns `specs/` and `~/.grok/tmp/5-notes.md`.
- **Honest `--json` (main / this PR, not #7):** stdout is `json_encode($result, JSON_PRETTY_PRINT)` (tunnels also `JSON_UNESCAPED_SLASHES`). Lists are JSON arrays. Creates are JSON objects. Failures are human `$this->error(...)` plus non-zero exit — not `{"ok":false,"error":"..."}`. Missing env still `exit(1)` with a human line.
- **Do not** add, link, or claim `docs/agent-output.md` unless that file exists on this tree (it does not; #7 has not landed).
- **Do not** write `{"ok":true` / `"ok": true` / "JSON envelope" in README, `AGENTS.md`, or `docs/*.md`.
- `jq` examples must match today's shape: `.[].name` on `zones --json`, `.id` on `tunnel:create --json`, `.tunnel.id` on `tunnel:expose --json`. Not `.data`.
- Fold from PR #4 by checking out **only** `docs/demo.md` and `docs/demo.cast.md` from `origin/cursor/hero-readme-demo-6412`, then patch (below). Do **not** take PR #4 README, badges, or `LICENSE`.
- README: keep `main` from-source install and the `## Development` quality-runners / `./bin/ci` block. Add `## Conduit ecosystem` (sibling links + `docs/conduit.md` + `AGENTS.md`). One line to the demo files. Drop Binary / Releases / `composer require` / "build a local PHAR" — that is PR #9.
- `docs/conduit.md` must include `tunnel:expose` (recipe + command-map row). That is commit `5361a53` — do not skip it.
- `composer.json` must stay valid JSON, keep `config.platform.php` = `8.3.33`, and keep main's `phpstan/phpstan` + `rector/rector` require-dev. Keywords/description from this PR are additive.
- `AGENTS.md`: prefer `--json`; local loop is `./bin/ci` (Pint, PHPStan, Rector, Pest). Residual `.github/workflows/ci.yml` is Pint+Pest only, **not** the house gate.
- `tunnel:create` / `tunnel:expose --json` include create-time `token` and `credentials_file`. Treat stdout as secret. Do not paste into tickets. API tokens still come from env, not prompts.
- Connector docblock already on the PR is harmless; do not expand `app/` in this crew.
- Do not: merge, deploy, second README crew, steal #7 envelope, steal #8 `dns:delete`/`dns:update`/`tunnel:get`, steal #3 LICENSE/Inspire, steal #6 `CloudflareCommand`, steal #9 PHAR.

### README conflict (rebase onto `e60415d`, stopped on `638d263`)

Keep **both** `## Development` (HEAD / main) and `## Conduit ecosystem` (incoming). Honest Agent JSON block (this is also what `5361a53` intended):

```markdown
## Agent JSON output

Pass `--json` to print the API result as pretty-printed JSON on stdout. Exit `0` on success, non-zero on failure (human errors go to the error output). Recipes: [docs/conduit.md](docs/conduit.md). Walkthrough: [docs/demo.md](docs/demo.md).
```

Zones comment stays `# JSON output`, not `# Agent JSON envelope`. Then `git rebase --continue` so `5361a53` can add `tunnel:expose` to `docs/conduit.md`. After rebase, checkout the two PR #4 demo files and patch.

### PR #4 demo fold (keep / change)

Keep: zones → DNS → tunnel script; `./cf list` (Laravel Zero built-in); `zones --name`; `dns:list --type/--name`; `tunnel:delete --force`; fake tables in `demo.cast.md`; recording tips; `jq '.[].name'` / `jq -r '.id'`.

Change:

- Document `--json` as pretty-printed `result`, not an envelope.
- Add `tunnel:expose` (landed via #13). Sample `--json` object keys: `tunnel`, `hostname`, `url`, `ingress_configured`, `dns_created`, `dns_record`, `configuration`, `dns_route_command`.
- Replace stale `tunnel:create` "Next steps: Configure ingress rules in ~/.cloudflared/config.yml" with current `displayCloudflaredRunSteps` (`cloudflared tunnel run --token …`, optional `cloudflared tunnel route dns …`).
- Cleanup of demo DNS: dashboard (or unmerged #8). Do not document `dns:delete` as if it exists.

## Done when

- [ ] `php -r 'json_decode(file_get_contents("composer.json"), true, 512, JSON_THROW_ON_ERROR); echo "JSON_OK\n";'`
- [ ] `python3 -c 'import json; c=json.load(open("composer.json")); assert c["config"]["platform"]["php"]=="8.3.33"; assert "phpstan/phpstan" in c["require-dev"]'`
- [ ] `test ! -f docs/agent-output.md`
- [ ] no `docs/agent-output.md` string in `README.md`, `AGENTS.md`, `docs/*.md`
- [ ] no `{"ok":true` / `"ok": true` / `JSON envelope` in those files
- [ ] `rg -n tunnel:expose docs/conduit.md`
- [ ] `test -f docs/demo.md && test -f docs/demo.cast.md`
- [ ] `vendor/bin/pest tests/Feature/ConduitDocsTest.php`

## Out of scope

- PR #4 hero README / badges / LICENSE
- PR #7 `OutputsJson` envelope and `docs/agent-output.md`
- PR #9 PHAR, Releases, Packagist install
- PR #8 extra DNS/tunnel CRUD
- PR #3 scaffold cleanup
- PR #6 `CloudflareCommand`
- Runtime JSON behavior changes
- A second README crew

## Open decisions

None. Verify commands are named. `#7` may later replace the `--json` paragraph; this PR must not pre-claim it.
