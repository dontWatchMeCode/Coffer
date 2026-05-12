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

## Implementation Plan

### Approach

Reuse existing `McpToken` auth system (no Sanctum). Dedicated API controllers. URL-based versioning (`api/v1/`). Eloquent API Resources.

### Auth

- Reuse `McpToken` model (`app/Models/McpToken.php`) + `AuthenticateMcpToken` middleware (`app/Http/Middleware/AuthenticateMcpToken.php`) — already handles Bearer token auth, user/team resolution, token hashing, expiry
- Token management UI already exists at `{current_team}/mcp` routes (`routes/web.php:46-49`)

### Backend

1. Create `routes/api.php` — register in `bootstrap/app.php`:
   ```php
   Route::prefix('v1')->middleware([AuthenticateMcpToken::class, 'throttle:api'])->group(...)
   ```
2. Create `app/Http/Resources/Api/` directory with Eloquent API Resources for each record type (`BookmarkResource`, `TaskResource`, `ContactResource`, `NoteResource`, `CollectionResource`, `SubscriptionResource`, `LogEntryResource`, `CalendarEventResource`, `TagResource`, `RecordLinkResource`)
3. Create `app/Http/Controllers/Api/` controllers:
   - `BookmarkController`, `ContactController`, `NoteController`, `CollectionController`, `SubscriptionController`, `LogEntryController`, `TaskController`, `CalendarEventController`
   - Each: `index` (list + search + cursor paginate), `store`, `show`, `update`, `destroy`
   - Follow existing CRUD pattern from controllers like `BookmarkController` (`app/Http/Controllers/Bookmarks/BookmarkController.php`) — team scoping via `whereBelongsTo($currentTeam)`, FormRequest validation
   - `SearchController` — mirrors `RecordSearchService` global search (`app/Services/RecordSearchService.php`)
   - `TagController`, `RecordLinkController` — for tag/link management
4. Team scoping: middleware already sets `currentTeam` on user (`AuthenticateMcpToken.php:51-52`) — inject `Team $currentTeam` via route model binding or resolve from `$request->user()->currentTeam`
5. Create `RateLimiter::for('api', ...)` in `AppServiceProvider` — e.g. 120 req/min per token
6. Add schema introspection endpoint: `GET api/v1/schema` — returns searchable types, columns, prefixes (from `RecordSearchRegistry`)

### Tests

7. Feature tests for each API endpoint (CRUD + search + pagination)
8. Test rate limiting, invalid token, wrong team scope, expired tokens

### Dependencies

- Unblocks: todo-15 (MCP setup instructions — can add API-specific snippets)

## Acceptance Criteria

- [ ] Add `api/v1/` route group with `AuthenticateMcpToken` middleware
- [ ] Create API token management UI (create, list, revoke tokens) — reuse existing MCP token UI
- [ ] Build dedicated API controllers in `app/Http/Controllers/Api/` for all record CRUD
- [ ] Add API search endpoint matching global search behavior
- [ ] Add API endpoints for tags and record links
- [ ] Return JSON responses via Eloquent API Resources
- [ ] Add URL-based API versioning (`api/v1/`)
- [ ] Add rate limiting for API routes (`RateLimiter::for('api', ...)`)
- [ ] Write feature tests for all API endpoints
