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

## Implementation Plan

### Approach

Unit tests using Pest for each service class. Use factories for model creation.

### Tests

1. `McpRecordService` — test CRUD for all 8 record types (tasks, calendar events, contacts, bookmarks, notes, collections, log entries, plus linking and tagging operations), validation errors, team scoping (operations only affect current team's records)
2. `McpTokenPermissionService` — test ability checks (allowed/denied), project scoping (token scoped to project vs team-wide), expired tokens return false, invalid abilities return false
3. `McpRecordValidator` — test validation rules per record type (required fields, type-specific rules like URL format for bookmarks, date for calendar events), edge cases (empty fields, invalid URLs, overly long strings)
4. `ActivityLogger` — test that only dirty attributes are logged (no log for unchanged records), correct model type resolution, correct team/user attribution

### Test Files

- `tests/Unit/Services/McpRecordServiceTest.php`
- `tests/Unit/Services/McpTokenPermissionServiceTest.php`
- `tests/Unit/Services/McpRecordValidatorTest.php`
- `tests/Unit/Services/ActivityLoggerTest.php`

### Dependencies

- Should be done after todo-3 (auth consolidation) to ensure auth patterns are stable

## Acceptance Criteria

- [ ] Unit tests for `McpRecordService` — record creation, update, deletion, validation
- [ ] Unit tests for `McpTokenPermissionService` — ability checks, project scoping
- [ ] Unit tests for `McpRecordValidator` — validation rules, edge cases
- [ ] Unit tests for `ActivityLogger` — logging behavior, dirty-only tracking
