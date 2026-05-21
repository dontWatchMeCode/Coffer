---
id: 19
section: frontend
status: todo
severity: medium
---

# Add Dedicated Search Page

Add a full search page that is accessible from the current search overlay and by keyboard.

## Current Implementation Notes

- The current search UI is `resources/js/components/nav/SearchOverlay.vue`.
- `Ctrl+K` / `Meta+K` opens the overlay today.
- Search results come from `SearchController` and `RecordSearchService` as grouped JSON results.
- Prefix help already exists in `SearchPrefixTooltip`.

## Decisions

1. **Route/page**: Inertia page at a named route, reusing the existing JSON search endpoint.
2. **Keyboard**: `Ctrl+K` opens overlay; pressing `Ctrl+K` again while overlay is open navigates to the full page.
3. **URL persistence**: Search query is kept in the URL query string for sharing/bookmarking.
4. **Pagination**: Full page raises the result limit above the overlay's `limit(10)`.

## Implementation Plan

1. Add a named route and Inertia page for dedicated search.
2. Add a button/link in `SearchOverlay.vue` to open the dedicated search page.
3. Add keyboard handling — second `Ctrl+K` while overlay is open navigates to full page.
4. Reuse search categories, prefix docs, and result row styles from the overlay where practical.

## Acceptance Criteria

- [ ] Dedicated search page is reachable from the search overlay
- [ ] Dedicated search page is reachable by `Ctrl+K+K` behavior
- [ ] Page supports all current record categories
- [ ] Prefix searches still work
- [ ] Search query can be retained in the URL or explicitly documented if not supported
- [ ] Tests cover route access and search result rendering
