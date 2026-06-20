---
id: 208
section: backend
status: todo
severity: high
---

# Switch Slugs To Team-Relative IDs

Replace team slug based routing with stable numeric team identifiers, then add team-relative public IDs for records that appear in URLs.

## Goal

- Stop using mutable team slugs as route identifiers
- Avoid exposing global record IDs in team-scoped URLs
- Keep internal primary keys unchanged for relationships and joins
- Preserve team scoping and authorization on every routed resource

## Recommended Phases

1. Switch team route parameters from slug to numeric team ID.
2. Update URL defaults, redirects, and frontend route calls to pass `currentTeam.id` / `team.id`.
3. Add `local_id` columns to URL-addressable team-owned records.
4. Backfill `local_id` as `1..N` per `team_id` for each affected table.
5. Resolve child route parameters by `team_id + local_id` instead of global `id`.
6. Return both internal `id` and public `localId` to Vue payloads.
7. Update frontend links/forms to pass `localId` for route params.
8. Add tests for duplicate `local_id` values across teams and cross-team 404/403 behavior.

## Team Route Changes

- Remove `Team::getRouteKeyName()` returning `slug`, or change route generation to use IDs explicitly.
- Change `EnsureTeamMembership` string lookup from `slug` to numeric `id`.
- Change `SetTeamUrlDefaults`, `HasTeams::switchTeam()`, auth responses, and team redirects from `$team->slug` to `$team->id`.
- Add numeric constraints for `{current_team}` and `{team}` routes where appropriate.
- Update Inertia shared team usage from `.slug` to `.id` for route generation.

## Record Route Changes

Add `local_id` to team-owned records used in URLs, likely:

- `bookmarks`
- `calendar_events`
- `contacts`
- `log_entries`
- `notes`
- `projects`
- `record_collections`
- `subscriptions`
- `tasks`
- `task_comments` if comment routes should avoid global IDs
- `mcp_tokens` if token management routes should avoid global IDs

Each table should have:

```php
$table->unsignedInteger('local_id');
$table->unique(['team_id', 'local_id']);
```

## ID Assignment

- Prefer a small `team_sequences` table or equivalent locked counter per team/table to avoid concurrent duplicate `local_id` values.
- Avoid using `max(local_id) + 1` without locking.
- Keep existing internal `id` values as primary keys.

## Slugs To Keep

- Keep tag slugs and subscription category slugs unless there is a separate product reason to remove them.
- These are semantic search/category identifiers, not primary URL route keys.

## Acceptance Criteria

- [ ] Team-prefixed routes use numeric team identifiers instead of slugs
- [ ] Team settings routes use numeric team identifiers instead of slugs
- [ ] Frontend route generation uses team IDs instead of team slugs
- [ ] URL-addressable team records have `local_id` with unique `team_id + local_id` constraints
- [ ] Existing records are backfilled with per-team local IDs
- [ ] New records receive race-safe per-team local IDs
- [ ] Child resources resolve by current team plus `local_id`, not global ID
- [ ] Tests prove the same `local_id` can exist in different teams
- [ ] Tests prove cross-team route access still fails
- [ ] Existing slug columns are retained only where still needed for labels/search/categories
