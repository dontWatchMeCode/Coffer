---
id: 1
section: backend
status: done
severity: high
---

# Add Infinite Scroll to List Views

All PageControllers use `->get()` with no pagination. Will degrade with scale.

## Approach

Endless scroll (not traditional pagination). Backend returns a cursor/next-page token; frontend appends results on scroll.

## Affected Controllers

- `BookmarkPageController`
- `ContactPageController`
- `NotePageController`
- `CollectionPageController`
- `SubscriptionPageController`
- `LogPageController`
- `CalendarPageController`
- `TaskPageController` (task lists within project views)

## Implementation Plan

### Approach

Full server-side search + cursor pagination using Laravel 13's built-in `cursorPaginate()`.

### Backend

1. Create `app/Traits/Filterable.php` — reusable `scopeFilter(Builder, string $query, array $searchableColumns)` that applies LIKE across columns (reuse pattern from `RecordSearchService::baseSearchQuery()` at `app/Services/RecordSearchService.php:124-138`)
2. Create `app/Http/Resources/CursorPaginatorResource` — wraps cursor paginator into Inertia-friendly shape (`data`, `next_cursor`, `prev_cursor`, `per_page`)
3. Update all 8 PageController `index()` methods (all currently use `->get()`):
   - `BookmarkPageController` (`app/Http/Controllers/Bookmarks/BookmarkPageController.php:25-28`)
   - `ContactPageController` (`app/Http/Controllers/Contacts/ContactPageController.php:25-28`)
   - `NotePageController` (`app/Http/Controllers/Notes/NotePageController.php:24-28`)
   - `CollectionPageController` (`app/Http/Controllers/Collections/CollectionPageController.php:25-29`)
   - `SubscriptionPageController` (`app/Http/Controllers/Subscriptions/SubscriptionPageController.php:25-29`)
   - `LogPageController` (`app/Http/Controllers/Log/LogPageController.php:17-20`)
   - `CalendarPageController` (`app/Http/Controllers/Calendar/CalendarPageController.php:25-30`)
   - `TaskPageController` (`app/Http/Controllers/Tasks/TaskPageController.php:31-40`) — projects + per-project tasks
   - Accept `?search=` query param, apply `Filterable` scope, replace `->get()` with `->cursorPaginate(25)`, pass to Inertia via resource
4. Add `$searchableColumns` constant to each model (or reuse `RecordSearchRegistry` definitions from `app/Services/RecordSearchRegistry.php:28-41`)

### Frontend

5. Create `resources/js/composables/useInfiniteScroll.ts` — composable using Inertia router + IntersectionObserver:
   - Accepts initial data from Inertia props, exposes `items`, `loading`, `canLoadMore`, `loadMore()`
   - Watches a sentinel element, triggers `loadMore()` on intersection
   - Appends results to items array, stops when no `next_cursor`
6. Update all 8 Vue Index pages (all currently use client-side `computed` search filters):
   - `resources/js/pages/bookmarks/Index.vue:30-43`
   - `resources/js/pages/contacts/Index.vue:56-71`
   - `resources/js/pages/notes/Index.vue:26-39`
   - `resources/js/pages/collections/Index.vue:31-46`
   - `resources/js/pages/subscriptions/Index.vue:30-43`
   - `resources/js/pages/log/Index.vue:95-115`
   - `resources/js/pages/calendar/Index.vue` (date-based filtering only)
   - `resources/js/pages/tasks/Index.vue` (project cards)
   - Remove client-side `computed` search filters, replace with Inertia partial reload (`only: ['items']`)
   - Wire `useInfiniteScroll`, add loading skeleton (existing: `resources/js/components/ui/skeleton/`)
7. Handle filter params (category, etc.) — append to cursor pagination query

### Tests

8. Feature test: pagination returns `next_cursor`, respects `per_page`, search filters work
9. Feature test: empty cursor / no more results behavior

### Dependencies

- Unblocked: todo-14 (log page redesign — completed)

## Acceptance Criteria

- [ ] Add cursor-based pagination to all list endpoints (return paginated response with `next_cursor`)
- [ ] Update Vue pages to fetch next page on scroll (IntersectionObserver or scroll event)
- [ ] Show loading skeleton while fetching next page
- [ ] Stop fetching when no more results (no next_cursor)
- [ ] Preserve search/filter compatibility with cursor pagination
