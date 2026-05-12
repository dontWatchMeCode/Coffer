---
id: 13
section: frontend
status: todo
severity: medium
---

# Add Log Entries to Global Search

Log entries are registered in `RecordSearchRegistry` (prefix `g`) but are excluded from the global search UI.

## Root Causes

1. **Backend**: `RecordLinkHelper::urlForModel()` returns empty string for `LogEntry` (no individual detail route). Search results are filtered out at `RecordSearchService::globalResultsForDefinition()` line 115 (`->filter(fn ($item) => $item['url'] !== '')`).
2. **Frontend**: `SearchOverlay.vue` hardcodes `SearchResponse` type, `emptyResults`, `allResults`, and `categories` without `log_entries`.

## Affected Files

- `app/Services/RecordLinkHelper.php` — line 35 returns `null` for `LogEntry`
- `app/Services/RecordSearchService.php` — line 115 filters out entries with empty URLs
- `resources/js/components/nav/SearchOverlay.vue` — missing `log_entries` everywhere

## Notes

- Log entries have no individual detail page (only the list page at `team.log.index`).
- Either add a detail route for log entries, or link search results to the log list page.

## Acceptance Criteria

- [ ] Decide on click target for log entry search results (detail page vs log list page)
- [ ] Update `RecordLinkHelper::urlForModel()` to return a URL for `LogEntry`
- [ ] Add `log_entries` to `SearchOverlay.vue` (`SearchResponse`, `emptyResults`, `allResults`, `categories`)
- [ ] Add log entry icon to `SearchOverlay.vue` categories
- [ ] Add `g` prefix to `SearchPrefixTooltip.vue` if missing
- [ ] Write feature test for global search returning log entries
