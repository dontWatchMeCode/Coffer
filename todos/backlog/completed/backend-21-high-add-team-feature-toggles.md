---
id: 21
section: backend
status: done
severity: high
---

# Add Team Feature Toggles

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

## Implementation Notes

- **Enum** `App\Enums\TeamFeature` — 8 cases matching all listed features, with `defaults()`, `options()`, `values()` helpers.
- **Database** `teams.feature_settings` JSON column via migration `2026_05_24_173054_add_feature_settings_to_teams_table.php` (pending — run `php artisan migrate`).
- **Model** `App\Models\Team` — `featureSettings()` merges persisted settings with `TeamFeature::defaults()` for safe fallback; `hasFeature()` single-method check.
- **Middleware** `App\Http\Middleware\EnsureTeamFeatureEnabled` — abort 404 if feature disabled. Applied per-feature route group in `routes/web.php`.
- **Navigation** — `AppSidebar.vue` reads `currentTeam.featureSettings` and filters nav items with `featureEnabled()`.
- **Search** — `RecordSearchRegistry::enabledDefinitions()` returns only types whose feature is enabled; used by `RecordSearchService`, `SearchPageController`, linked-record candidate lookup.
- **Linked Records** — `ResolvesLinkableRecord` and `RecordSearchRegistry::teamAllowsType()` gate linking/tagging operations.
- **MCP** — `McpRecordService` (all CRUD + search) and `McpTokenPermissionService` check `RecordSearchRegistry::teamAllowsType()` before operating.
- **Activity History** — `ActivityHistoryController` uses `RecordSearchRegistry::teamAllowsType()` to hide disabled-feature subject types.
- **Team Settings UI** — `TeamSettingsForm.vue` renders checkboxes for all features; `SaveTeamRequest` validates and merges with existing settings.
- **Tests** — `TeamFeatureToggleTest.php` (131 lines) covers: enable/disable via settings, route blocking, search exclusion, linked-record candidate exclusion, tag/link blocking, and activity history hiding.

## Current Implementation Notes

- Team settings currently only updates the team name through `TeamSettingsForm` and `TeamController`.
- The `teams` table currently stores `name`, `slug`, and `is_personal`; there is no feature settings column/table.
- Search and linked-record behavior are centralized through `RecordSearchRegistry`, `RecordSearchService`, and `RecordLink` helpers.
- MCP record access uses `McpRecordResolver`, `McpRecordService`, and token permissions.
- App navigation is rendered in `AppSidebar.vue`.

## Decisions

- Persist feature settings as a JSON column on `teams`.
- Disabled features are hidden from navigation/search and blocked from normal UI routes and writes.
- Team owners/admins can enable or disable features in Team Settings, but cannot access disabled feature pages.
- Existing records are preserved but hidden while their feature is disabled.
- MCP/API capabilities for disabled features are omitted.
- Activity entries related to disabled features are preserved but hidden.

## Acceptance Criteria

- [x] Team settings includes toggles for all listed features
- [x] Disabled features are hidden from app navigation
- [x] Disabled features block normal UI routes and writes
- [x] Disabled features are excluded from global search
- [x] Disabled features are excluded from linked-record candidate search
- [x] Disabled features are excluded or denied in MCP schema/search/CRUD tools
- [x] Existing records are not deleted when a feature is disabled
- [x] Tests cover disabled behavior across routes, search, linked records, and MCP
