---
id: 28
section: frontend
status: done
severity: medium
---

# Add time estimate field to tasks

Add a configurable time estimate field to tasks, displayed above the progress slider in `TaskSidebar`.

## Current State

- `tasks` table has `progress` (integer, default 0) — no time estimate column
- `TaskSidebar.vue` renders the progress slider at line ~401
- `TaskEditForm.vue` handles task field editing

## Acceptance Criteria

- [x] Add migration: add nullable `time_estimate` integer column to `tasks` table (stores minutes)
- [x] Update `Task` model fillable/casts
- [x] Update `TaskFactory` to include `time_estimate`
- [x] Update `TaskController::store`/`update` validation to accept `time_estimate`
- [x] Update `TaskPageDataService` to include `time_estimate` in task payload
- [x] Update `TaskItem` type to include `timeEstimate?: number | null`
- [x] Add time estimate input (e.g. hours/minutes picker or plain number input) in `TaskSidebar.vue` above the progress slider section
- [x] Display formatted time estimate when not editing (e.g. "2h 30m" or "45m")
- [x] Update task edit form/sidebar to persist `time_estimate` on change
- [x] Update tests

## Files

- `resources/js/components/pages/tasks/TaskSidebar.vue` — add input above progress slider
- `resources/js/components/pages/tasks/TaskEditForm.vue` — may need time estimate field if separate from sidebar
- `app/Models/Task.php`
- `app/Http/Controllers/Tasks/TaskController.php`
- `app/Services/TaskPageDataService.php`
- `database/factories/TaskFactory.php`
- `resources/js/types/tasks.ts`
