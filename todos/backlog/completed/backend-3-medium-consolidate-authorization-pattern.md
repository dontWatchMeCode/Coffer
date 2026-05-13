---
id: 3
section: backend
status: done
severity: medium
---

# Consolidate Authorization Pattern

14 policy files exist (12 registered via `Gate::policy()` in `AppServiceProvider`), but CRUD controllers rely solely on `AuthorizesTeamResource` in FormRequests. Policies are effectively dead code.

## Current State

- `AppServiceProvider` registers 12 policies via `Gate::policy()` (2 unregistered: `SubscriptionPolicy`, `TeamPolicy`)
- Controllers never call `$this->authorize()`
- `AuthorizesTeamResource` trait in FormRequests handles authorization independently
- Two parallel auth systems doing the same job

## Implementation Plan

### Approach

Keep both layers but wire them properly. Policies become the single source of truth for authorization logic. FormRequests keep validation + team membership check only.

### Current State

- 14 policy files in `app/Policies/` (12 registered via `Gate::policy()` in `AppServiceProvider`, 2 unregistered: `SubscriptionPolicy`, `TeamPolicy`)
- 24 FormRequests use `AuthorizesTeamResource` trait (`app/Http/Requests/Tasks/AuthorizesTeamResource.php`) — provides `isTeamMember()`, `currentTeam()`, `isCommentOwner()`
- Controllers never call `$this->authorize()` — auth is entirely in FormRequests

### Steps

1. Audit all 14 policies — ensure each has:
   - `before()` method checking `$user->belongsToTeam($team)` for team membership
   - Correct method implementations (`viewAny`, `view`, `create`, `update`, `delete`)
   - Register `SubscriptionPolicy` and `TeamPolicy` in `AppServiceProvider`
2. Add `$this->authorize()` calls to all CRUD controllers:
   - `BookmarkController`, `ContactController`, `NoteController`, `CollectionController`, `SubscriptionController`, `LogEntryController`, `TaskController`, `CalendarEventController`
   - Call in `store`, `update`, `destroy` methods before performing the action
   - Pass model instance for `update`/`destroy`, pass team for `store`
3. Simplify `AuthorizesTeamResource` trait — keep only team membership check (`isTeamMember()`), remove any per-resource authorization logic (delegate to policies)
4. Add architecture test (`tests/Architecture/AuthorizationTest.php`):
   - `arch()->expect()->toUsePolicy()` — enforces controllers call `authorize()`
   - Verify no dead policies (all registered policies are used by at least one controller)

### Dependencies

- Do before todo-5 (moving trait) since it changes the trait's responsibilities

## Acceptance Criteria

- [ ] Wire policies into all CRUD controllers via `$this->authorize()`
- [ ] Simplify `AuthorizesTeamResource` to team membership check only
- [ ] Register `SubscriptionPolicy` and `TeamPolicy` in `AppServiceProvider`
- [ ] Add architecture test enforcing controllers use `authorize()`
- [ ] Update architecture tests if needed
