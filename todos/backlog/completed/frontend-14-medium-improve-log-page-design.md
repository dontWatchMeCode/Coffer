---
id: 14
section: frontend
status: done
severity: medium
---

# Improve Log Page Design and Features

The Log page needs a visual refresh and missing functionality.

## Current Issues

- Layout is chat-style with no sidebar filtering or search structure
- Log entries can only be created and deleted — no edit/update
- No activity history on log entries

## Dependencies

- Should be sequenced after [todo-1](todo-backend-1-high-add-pagination.md) (infinite scroll for LogPageController — avoid adding scroll to a page that will be redesigned).
- Related to [todo-13](todo-frontend-13-medium-add-log-to-global-search.md) — todo-13 covers global search integration; this covers page-local sidebar search. No direct conflict but coordinate to avoid duplicating search UI logic.

## Implementation Plan

### Approach

Redesign with sidebar layout matching other record pages. Add edit capability and activity tracking.

### Dependencies

- **Requires todo-1** (pagination) — the log list needs infinite scroll before redesign
- Related to todo-13 (log search) — todo-13 covers global search integration; this covers page-local sidebar search

### Backend

1. Verify `LogEntryPolicy` `update` method (`app/Policies/LogEntryPolicy.php:27-29`) — already exists with team membership check
2. Create `UpdateLogEntryRequest` FormRequest in `app/Http/Requests/Log/` — validate `body` and `category`
3. Add `LogEntryController@update()` method + `PUT/PATCH` route in `routes/web.php`
4. Add `Spatie\Activitylog\Traits\LogsActivity` trait to `LogEntry` model — track changes to `body` and `category`

### Frontend

5. Redesign `resources/js/pages/log/Index.vue`:
   - Add sidebar (consistent with bookmarks, contacts, notes layout):
     - Search input (server-side via pagination from todo-1)
     - Category filter buttons (move from current inline position at line ~95-115)
   - Main content area:
     - Keep quick-entry composer at top (inline add)
     - Log entry list with infinite scroll (from todo-1)
     - Add inline edit capability (click to edit, or edit modal using existing `Dialog` component)
   - Add activity history panel (slide-out using existing `Sheet` component, or collapsible section):
     - Show timestamp + old/new values for `body` and `category` changes
6. Ensure responsive design matches other record pages (sidebar collapses on mobile)

### Tests

7. Feature test: edit log entry via PUT endpoint
8. Feature test: activity log records body/category changes
9. Feature test: authorization — only team members can edit

## Direction

Move toward a sidebar filter/search layout consistent with other record areas (contacts, bookmarks, etc.) rather than the current chat-style design.

## Acceptance Criteria

- [x] Redesign Log page with sidebar for category filtering and search
- [x] Add edit/update functionality for log entries (new controller method + route)
- [x] Add `LogsActivity` trait to `LogEntry` model (log `body` and `category` changes)
- [x] Add activity history panel to Log page
- [x] Maintain the quick-entry composer (add entry inline)
- [x] Ensure responsive design matches other record pages
