---
id: 21
section: backend
status: planning
severity: high
---

# Plan Team Feature Toggles

Add team-level feature toggles that can disable app areas and their related records.

## Features

- Tasks
- Calendar
- Contacts
- Bookmarks
- Subscriptions
- Notes
- Log
- Collections

## Current Implementation Notes

- Team settings currently only updates the team name through `TeamSettingsForm` and `TeamController`.
- The `teams` table currently stores `name`, `slug`, and `is_personal`; there is no feature settings column/table.
- Search and linked-record behavior are centralized through `RecordSearchRegistry`, `RecordSearchService`, and `RecordLink` helpers.
- MCP record access uses `McpRecordResolver`, `McpRecordService`, and token permissions.
- App navigation is rendered in `AppSidebar.vue`.

## Planning Work

1. Decide persistence shape: JSON column on `teams` versus a normalized team feature settings table.
2. Decide disabled behavior for existing records: hide only, block reads/writes, or preserve direct admin access.
3. Define one central capability service so routes, nav, search, MCP, linked records, and API tokens use the same source of truth.
4. Decide whether disabling a feature should affect historical activity log entries.
5. Decide how team owners/admins recover a disabled feature if the UI is hidden.

## Acceptance Criteria

- [ ] Team settings includes toggles for all listed features
- [ ] Disabled features are hidden from app navigation
- [ ] Disabled features block normal UI routes and writes
- [ ] Disabled features are excluded from global search
- [ ] Disabled features are excluded from linked-record candidate search
- [ ] Disabled features are excluded or denied in MCP schema/search/CRUD tools
- [ ] Existing records are not deleted when a feature is disabled
- [ ] Tests cover disabled behavior across routes, search, linked records, and MCP
