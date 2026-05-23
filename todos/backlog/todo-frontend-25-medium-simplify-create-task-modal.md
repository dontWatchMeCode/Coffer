---
id: 25
section: frontend
status: todo
severity: medium
---

# Simplify Create Task modal in project view

The `CreateTaskDialog` component currently accepts title, description, status, assignee, and due date. Simplify it to only accept a **title** and navigate to the newly created task after submission.

## Acceptance Criteria

- [ ] Remove description, status, assignee, and due date fields from `CreateTaskDialog`
- [ ] Remove unused imports: `RichTextEditor`, `trimStoredRichText`, `Select*` components (`Label` is still needed for the title field)
- [ ] Remove unused refs: `createDescription`, `selectedStatus`, `selectedAssignee`
- [ ] Remove `members` and `statuses` props (no longer needed)
- [ ] Update all usages of `CreateTaskDialog` to drop the removed props
- [ ] On successful create, navigate to the new task's show page (use the response from the controller)
- [ ] Ensure the `TaskController::store` returns the created task slug/id for navigation
- [ ] Update or add tests to verify the simplified form and navigation behavior

## Files

- `resources/js/components/pages/tasks/CreateTaskDialog.vue` — main component to simplify
- `resources/js/pages/tasks/Show.vue` — usage site, remove `members`/`statuses` props
- `app/Http/Controllers/Tasks/TaskController.php` — ensure store returns task identifier for redirect
- `tests/Feature/Tasks/TaskManagementPageTest.php` — update/create tests
