---
id: 203
section: infra
status: done
severity: medium
---

# Plan FrankenPHP Deployment

Production switch to classic FrankenPHP behind reverse proxy, with Postgres provided by Docker Compose. Fully implemented.

## Implementation Notes

- `laravel/octane` not installed — Octane worker mode intentionally deferred; worker-safety hardening landed instead (see below) so a future Octane switch inherits no cross-request leaks.
- Docker Compose (`compose.yml`) defines five services: `app` (FrankenPHP HTTP), `setup` (one-shot backup, migrations, and Laravel cache warmup), `queue` (queue worker), `scheduler`, and `postgres` (Postgres 18).
- The `setup` service shares `bootstrap/cache` with app, queue, and scheduler via the `app-cache` volume so cached config/routes/events are visible to runtime services.
- The `setup` service runs `php artisan backup:run --only-db --disable-notifications || true` (non-blocking) before `optimize:clear && migrate --force && optimize`. Backups store on the `backups` filesystem disk (`storage/app/backups`) mapped to the `app-backups` Docker volume, which is also mounted to `app`, `queue`, and `scheduler`. The backup monitor in `config/backup.php` also uses the `backups` disk.
- App binds to `127.0.0.1:8080:80` — listens on port 80 internally, published on 127.0.0.1:8080 for reverse proxy (Traefik or similar).
- Caddyfile disables `auto_https` (TLS terminated upstream) and serves on `:80`; no `encode` directive — no backend compression.
- Config defaults remain sqlite; production deployment switches to Postgres via `.env` overrides (`DB_CONNECTION=pgsql`) for app database, cache, sessions, and queues.
- Pulse enabled with database ingest/storage (writes to Postgres in production).
- `subscriptions:rollover` scheduled command exists; scheduler service runs `php artisan schedule:work` to execute it.
- Healthcheck on app service hits `/up` endpoint internally on port 80.
- Dockerfile installs Node.js/npm for Vite asset compilation (multi-stage build) and `postgresql-client` (for `pg_dump` used by `backup:run --only-db`).
- `php artisan storage:link` is baked into the image at build time (Dockerfile line 65).
- Dockerfile asset build stage includes a placeholder `APP_KEY`; the real key must be set in production `.env`.

## Design Decisions

- Classic FrankenPHP chosen over Octane; Octane re-evaluated only after production behavior is stable. Worker-safety hardening (below) removes the known blockers.
- Worker-safety hardening (uncommitted at time of writing, covered by `tests/Feature/Octane/WorkerReuseTest.php`, 3 tests):
  - `phpstan.neon` enables `checkOctaneCompatibility`.
  - URL-default leaks fixed: `SetTeamUrlDefaults` always resets `URL::defaults` (guest-safe), moved before `HandleInertiaRequests` in `bootstrap/app.php`; `HasTeams::switchTeam` and login/register responses no longer set global URL defaults (team switch now resets them in `EnsureTeamMembership`).
  - MCP guard fix: `AuthenticateMcpToken` calls `Auth::forgetGuards()` after resolving the token user; `McpRequestContext` prefers the bound token's team over session state.
  - MCP resets: `routes/ai.php` runs `SetTeamUrlDefaults` + `HandleAppearance` on the MCP route (`Mcp::web()` skips the `web` group); `AppServiceProvider::boot` shares cold-worker appearance/view defaults.
  - Fortify response contracts changed `singleton` → `bind` (no stale per-request state on reused workers).
  - Telescope disabled by default (`TELESCOPE_ENABLED=false`), ignores `telescope*`/`up`, model hydrations off; Pulse ignores `/up` healthchecks; Sentry default `sample_rate` 0.2 and `continue_after_response` false.
  - CSP headers registered: `AddCspHeaders` appended to `web` middleware (policy in `config/csp.php`).
  - `subscriptions:rollover` chunked (`chunkById(500)`, single query, no per-team loop) with `withoutOverlapping()->onOneServer()`; `telescope:prune --hours=72` and a daily thumbnail `.tmp` sweep scheduled.
  - Queue `after_commit: true` (database + redis); `queue` service recycles via `--max-jobs=1000 --max-time=3600`.
- Postgres used for app database, cache, sessions, queues, Pulse storage, and Pulse ingest.
- HTTP, queue workers, and scheduler run as separate Compose services.
- Reverse proxy targets `http://127.0.0.1:8080`.
- Release steps: build image, then start services; the one-shot `setup` service runs backup, migrations, and cache warmup before HTTP, queue, and scheduler services.

## Acceptance Criteria

- [x] Deployment mode is chosen: classic FrankenPHP
- [x] Production backing services are documented or configured
- [x] HTTP start command is defined
- [x] Queue worker process is defined if queues remain enabled
- [x] Scheduler process is defined for `subscriptions:rollover`
- [x] Release checklist includes cache/build/migration/reload steps

## Release Checklist

1. Set production `.env` values, including `APP_URL`, `APP_KEY`, `DB_*`, `TRUSTED_PROXIES=*`, and `SESSION_SECURE_COOKIE=true`. Run `php artisan key:generate --force` first if `APP_KEY` is not already set.
2. Run `docker compose build`.
3. Run `docker compose up -d --force-recreate`; Compose starts Postgres, then runs the one-shot `setup` service (`backup:run --only-db --disable-notifications || true`, `optimize:clear`, `migrate --force`, `optimize`) before starting `app`, `queue`, and `scheduler`.
4. Run `docker compose run --rm app php artisan storage:link` if the public storage link does not exist (already baked into image build, but safe to re-run).
5. On later deploys, run `docker compose exec app php artisan queue:restart` before recreating `queue` and `scheduler`.
