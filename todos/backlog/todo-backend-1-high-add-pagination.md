---
id: 1
section: backend
status: todo
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

## Acceptance Criteria

- [ ] Add cursor-based pagination to all list endpoints (return paginated response with `next_cursor`)
- [ ] Update Vue pages to fetch next page on scroll (IntersectionObserver or scroll event)
- [ ] Show loading skeleton while fetching next page
- [ ] Stop fetching when no more results (no next_cursor)
- [ ] Preserve search/filter compatibility with cursor pagination
