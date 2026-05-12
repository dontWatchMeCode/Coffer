---
id: 2
section: backend
status: todo
severity: medium
---

# Add Unit Tests for Services

Services lack unit test coverage.

## Services Needing Tests

- `McpRecordService`
- `McpTokenPermissionService`
- `McpRecordValidator`
- `ActivityLogger`

## Acceptance Criteria

- [ ] Unit tests for `McpRecordService` — record creation, update, deletion, validation
- [ ] Unit tests for `McpTokenPermissionService` — ability checks, project scoping
- [ ] Unit tests for `McpRecordValidator` — validation rules, edge cases
- [ ] Unit tests for `ActivityLogger` — logging behavior, dirty-only tracking
