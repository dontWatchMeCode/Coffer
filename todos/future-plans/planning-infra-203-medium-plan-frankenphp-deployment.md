---
id: 203
section: infra
status: planning
severity: medium
---

# Plan FrankenPHP Deployment

Plan the production switch to FrankenPHP and decide whether to run classic mode first or install Octane for worker mode.

## Current Implementation Notes

- `laravel/octane` is not installed, so Octane worker mode is not available yet.
- No Dockerfile, Caddyfile, Procfile, or production FrankenPHP start command exists in the repo.
- Production-like defaults currently use SQLite with database-backed cache, sessions, and queues.
- Pulse is enabled with database ingest/storage, which can add write pressure under concurrent workers.
- A scheduler exists for `subscriptions:rollover` and will need a separate process.

## Planning Work

1. Decide classic FrankenPHP versus Octane worker mode for the first deployment.
2. Decide production database, cache, session, and queue backends before enabling multiple workers.
3. Define process layout for HTTP, queue workers, scheduler, and optional Pulse ingestion.
4. Add deployment/start configuration for the target host.
5. Define release steps for migrations, `storage:link`, `php artisan optimize`, and worker reloads.

## Acceptance Criteria

- [ ] Deployment mode is chosen: classic FrankenPHP or Octane worker mode
- [ ] Production backing services are documented or configured
- [ ] HTTP start command is defined
- [ ] Queue worker process is defined if queues remain enabled
- [ ] Scheduler process is defined for `subscriptions:rollover`
- [ ] Release checklist includes cache/build/migration/reload steps
