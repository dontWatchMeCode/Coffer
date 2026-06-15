---
id: 205
section: backend
status: done
severity: low
---

# Split `routes/web.php` into per-feature route files

Each feature group (calendar, contacts, bookmarks, subscriptions, log, notes, collections, tasks) is already isolated behind `EnsureTeamFeatureEnabled` — they'd be natural one-file-per-feature.

## Proposal

- Keep shared routes (home, dashboard, search, mcp, links, tags, activity-history, invitations) in `web.php`
- Inside the `{current_team}` group, `require` one file per feature: `routes/features/calendar.php`, `routes/features/contacts.php`, etc.
- `routes/features/` directory with individual files, each containing the `EnsureTeamFeatureEnabled` group block
- Follows the pattern already used for `settings.php`

## Benefits

- Smaller diffs per feature
- Fewer merge conflicts
- Easier to find routes for a specific feature
- Consistent with existing `require __DIR__.'/settings.php'` pattern
