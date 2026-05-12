---
id: 3
section: backend
status: todo
severity: medium
---

# Consolidate Authorization Pattern

14 policy files exist (12 registered via `Gate::policy()` in `AppServiceProvider`), but CRUD controllers rely solely on `AuthorizesTeamResource` in FormRequests. Policies are effectively dead code.

## Current State

- `AppServiceProvider` registers 12 policies via `Gate::policy()` (2 unregistered: `SubscriptionPolicy`, `TeamPolicy`)
- Controllers never call `$this->authorize()`
- `AuthorizesTeamResource` trait in FormRequests handles authorization independently
- Two parallel auth systems doing the same job

## Acceptance Criteria

- [ ] Decide on single authorization strategy (policies or FormRequests)
- [ ] Remove unused layer or wire policies into controllers
- [ ] Update architecture tests if needed
