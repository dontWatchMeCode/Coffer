---
id: 202
section: backend
status: done
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

- [x] Project settings includes status option management
- [x] Team settings includes default status options for new projects
- [x] New projects copy the team's current default status options
- [x] Existing projects retain their current status options
- [x] Existing enum-backed task statuses are migrated without changing task state
- [x] Task create/edit/status dropdowns use the project's configured statuses
- [x] Invalid cross-project or cross-team statuses are rejected server-side
- [x] Completion timestamp behavior still works for completed-equivalent statuses
- [x] Tests cover defaults, project customization, validation, and task status updates

## Implementation Notes (updated)

- Task model no longer casts `status` to `TaskStatus` enum; stores as string. `setStatusAttribute` converts enum instances to strings.
- `syncCompletionTimestamp` checks `TaskStatus::Completed->value` exactly; custom statuses with equivalent meaning do not auto-set `completed_at`.
- No data migration needed — existing enum-backed status values are already valid strings.
- Team `default_task_status_options` and project `status_options` stored as nullable JSON columns.
- MCP record validation (create/update) also scoped to project's configured status values.
