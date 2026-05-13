---
id: 19
section: frontend
status: planning
severity: medium
---

# Add Dedicated Search Page

Add a full search page that is accessible from the current search overlay and by keyboard.

## Current Implementation Notes

- The current search UI is `resources/js/components/nav/SearchOverlay.vue`.
- `Ctrl+K` / `Meta+K` opens the overlay today.
- Search results come from `SearchController` and `RecordSearchService` as grouped JSON results.
- Prefix help already exists in `SearchPrefixTooltip`.

## Planning Work

1. Decide the route, page name, and whether the full page reuses the existing JSON endpoint or receives Inertia props.
2. Decide keyboard behavior for `Ctrl+K+K`, e.g. pressing `Ctrl+K` again or pressing `K` while the overlay is already open.
3. Decide whether the full page should support URL query persistence so searches can be shared/bookmarked.
4. Decide pagination or result limits before broad searches outgrow the overlay's `limit(10)` behavior.

## Implementation Plan

1. Add a named route and Inertia page for dedicated search.
2. Add a button/link in `SearchOverlay.vue` to open the dedicated search page.
3. Add keyboard handling for the agreed `Ctrl+K+K` behavior.
4. Reuse search categories, prefix docs, and result row styles from the overlay where practical.

## Acceptance Criteria

- [ ] Dedicated search page is reachable from the search overlay
- [ ] Dedicated search page is reachable by `Ctrl+K+K` behavior
- [ ] Page supports all current record categories
- [ ] Prefix searches still work
- [ ] Search query can be retained in the URL or explicitly documented if not supported
- [ ] Tests cover route access and search result rendering
