---
id: 202
section: backend
status: todo
severity: medium
---

# Add Custom Project Statuses

Allow each project to define its own task status options instead of using only the global `TaskStatus` enum values.

## Scope

- Project settings can add, rename, reorder, and remove available task statuses.
- Tasks in a project can only use statuses configured for that project.
- Existing projects keep the current default statuses until changed.
- Team settings can define the default status options applied to newly created projects.

## Implementation Notes

- Preserve explicit completed/dropped behavior so completion timestamps and hidden-task filters still work.
- Decide how to migrate existing enum-backed task statuses before replacing validation/UI assumptions.
- Keep project status options scoped through their owning project and team to prevent cross-team leakage.

## Acceptance Criteria

- [ ] Project settings includes status option management
- [ ] Team settings includes default status options for new projects
- [ ] New projects copy the team's current default status options
- [ ] Existing projects retain their current status options
- [ ] Existing enum-backed task statuses are migrated without changing task state
- [ ] Task create/edit/status dropdowns use the project's configured statuses
- [ ] Invalid cross-project or cross-team statuses are rejected server-side
- [ ] Completion timestamp behavior still works for completed-equivalent statuses
- [ ] Tests cover defaults, project customization, validation, and task status updates
