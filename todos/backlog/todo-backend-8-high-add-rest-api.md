---
id: 8
section: backend
status: todo
severity: high
---

# Add REST API

Add a public REST API that mirrors MCP record functionality, authenticated via Laravel Sanctum tokens.

## Scope

- All record types: tasks, calendar events, contacts, bookmarks, notes, collections, log entries, subscriptions
- CRUD operations, search, tags, record links
- Schema introspection endpoint
- Scoped per team (token belongs to a user, operations use their active team context)

## Acceptance Criteria

- [ ] Install and configure Laravel Sanctum
- [ ] Create API token management UI (create, list, revoke tokens)
- [ ] Add `api/` route group with Sanctum auth middleware
- [ ] Build API controllers (or adapt existing controllers) for all record CRUD
- [ ] Add API search endpoint matching global search behavior
- [ ] Add API endpoints for tags and record links
- [ ] Return JSON responses via Eloquent API Resources
- [ ] Add API versioning (e.g. `api/v1/`)
- [ ] Add rate limiting for API routes
- [ ] Write feature tests for all API endpoints
