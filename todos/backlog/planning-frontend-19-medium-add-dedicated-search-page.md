---
id: 19
section: frontend
status: planning
severity: medium
---

# Add Dedicated Search Page

Add a full search page that is accessible from the current search overlay and by keyboard.

## What's Implemented

- `SearchPageController` at `GET /{team}/search/page` with `?q=`, `?type=`, `?tag=` params
- `RecordSearchService::global()` accepts `$limit` param (default 10, page uses 50)
- `RecordSearchService::browse()` for type-only browsing (returns all records of a type)
- Inertia page at `resources/js/pages/search/Index.vue` with right-side sticky sidebar (matches `EditorSidebarLayout` pattern: `xl:sticky xl:top-8 xl:w-[280px]`)
- `SearchFilterSidebar.vue` component with record type list and tag badges
- Sidebar type filter (9 types) and tag filter (team tags as toggleable badges)
- Active filter chips above results with dismiss X buttons
- URL persistence for query + type + tag
- `SearchOverlay.vue`: "View all" button in footer, Ctrl+K+K navigates to full page, collections added to overlay results
- Non-ok fetch responses clear stale data in both overlay and page
- Route throttled at 30 req/min
- 11 feature tests + all 23 existing search tests passing

## Needs Refinement

- **Design/behaviour**: The sidebar layout is functional but needs visual polish and UX decisions
  - How should results look? Current is a simple list — should it match the card/list views from index pages?
  - Should result items show more detail (created date, tags, etc.)?
  - Should the search page support pagination for type-only browsing (currently capped at 50)?

## Known Tech Debt

- `typeIconMap` duplicated across Index.vue, SearchFilterSidebar.vue, SearchOverlay.vue
- `SearchResponse` type duplicated across Index.vue and SearchOverlay.vue
- `log_entries` → `"Log"` label logic duplicated in 3 places
- Could extract shared types to `resources/js/types/search.ts` and icon map to a composable

## Acceptance Criteria

- [x] Dedicated search page is reachable from the search overlay
- [x] Dedicated search page is reachable by `Ctrl+K+K` behavior
- [x] Page supports all current record categories (including collections)
- [x] Prefix searches still work
- [x] Search query retained in URL query string for sharing/bookmarking
- [x] Tests cover route access and search result rendering
- [ ] Design and UX polished
