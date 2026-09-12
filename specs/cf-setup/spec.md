# SPEC: cf setup writes .env after VerifyToken

Status: LOCKED
PR: [conduit-ui/cloudflare#10](https://github.com/conduit-ui/cloudflare/pull/10)
Branch: `cursor/cf-setup-auth-6412`

**Story:** Other `cf` commands say the token and account id must be set. The body copies `.env.example` by hand and finds out the token is dead on the first real call. Agents have no non-interactive onboarding.

## Locked

- `./cf setup` is the onboarding path. Hand-copy of `.env.example` stays as fallback in README.
- Verify first: Saloon `GET https://api.cloudflare.com/client/v4/user/tokens/verify` (`App\Integrations\Cloudflare\Requests\User\VerifyToken`) with `Authorization: Bearer <token>`.
- Write `base_path('.env')` **only after** HTTP success **and** envelope `success === true`. Failure, missing values, or empty-after-trim must not create or mutate `.env`.
- Account ID is stored as given. Verify does not prove the account exists or that the token can see it.
- Envelope success is enough. Do not add `result.status === active`, permission/scope checks, or an accounts list call.
- Non-interactive flags:
  - `--token=`
  - `--account-id=`
  - `--non-interactive` (missing value → exit 1, no prompt)
  - `--json` (pretty JSON; never the API token)
- Interactive (no `--non-interactive`): `secret()` for the token, `ask()` for the account id. Do not read existing `CLOUDFLARE_*` env as defaults.
- `.env` keys: `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ACCOUNT_ID`. Replace an existing key line; append if absent. Quote values that contain whitespace `# " ' =`.
- Success JSON: `{success: true, message, account_id}`. Failure JSON: `{success: false, message}`. Text success may print account id, never the token.
- Saloon layout matches zones/tunnels: `CloudflareConnector::user()` → `UserResource` → `Requests\User\VerifyToken`.
- No `.php-bin` symlink. `.gitignore` already has `.php-bin`.
- You own (research): `specs/`, `~/.grok/tmp/10-notes.md`.
- Build owns: `app/Commands/SetupCommand.php`, `UserResource.php`, `Requests/User/VerifyToken.php`, `CloudflareConnector::user()`, `.env.example`, README setup blurb.
- Test owns: `tests/Feature/SetupCommandTest.php`.
- Do not: merge, deploy, new wrapper, Asgard exec of `cf` (that is `cf-asgard-readonly` / Asgard#250).

## Done when

- [ ] `vendor/bin/pest tests/Feature/SetupCommandTest.php --compact` covers:
  - `--non-interactive` missing `--token` → exit 1
  - `--non-interactive` missing `--account-id` → exit 1
  - mocked verify fail → exit 1 **and** `.env` unchanged
  - `--json` verify fail → JSON `success: false`, exit 1
  - mocked verify success + `--non-interactive --json` → exit 0, `.env` contains both keys, JSON `success: true`, output does not contain the token; restore `.env` in `finally`
- [ ] `./bin/ci` on this clone (Pint, PHPStan, Rector dry-run, Pest)

## Out of scope

- `gh pr merge` / land-when-gated / deploy
- Account membership lookup, token permission matrix (Zone:Read / DNS:Edit / Tunnel:Edit)
- Interactive Pest (manual only)
- Wrangler / `cloudflared login`
- `--dry-run` (#15), `dns:upsert` (#14), `tunnel:status --wait` (#16)

## Open decisions

None. Disabled/expired `result.status` with envelope `success: true` stays write-allowed unless a later SPEC says otherwise.
