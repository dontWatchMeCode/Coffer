---
id: 14
section: frontend
status: todo
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

## Direction

Move toward a sidebar filter/search layout consistent with other record areas (contacts, bookmarks, etc.) rather than the current chat-style design.

## Acceptance Criteria

- [ ] Redesign Log page with sidebar for category filtering and search
- [ ] Add edit/update functionality for log entries (new controller method + route)
- [ ] Add `LogsActivity` trait to `LogEntry` model (log `body` and `category` changes)
- [ ] Add activity history panel to Log page
- [ ] Maintain the quick-entry composer (add entry inline)
- [ ] Ensure responsive design matches other record pages
