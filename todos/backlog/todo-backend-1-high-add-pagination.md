---
id: 1
section: backend
status: todo
severity: high
---

# Add Pagination to List Views

All PageControllers use `->get()` with no pagination. Will degrade with scale.

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

- [ ] Add cursor-based or offset pagination to all list endpoints
- [ ] Update Vue pages to handle paginated responses (load more / page controls)
- [ ] Preserve search/filter compatibility with pagination
