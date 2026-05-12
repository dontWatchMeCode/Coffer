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

## Implementation Plan

### Approach

Link search results to log list page (`team.log.index`). No new detail page.

### Backend

1. Update `RecordLinkHelper::urlForModel()` (`app/Services/RecordLinkHelper.php:26-37`):
   - Change existing `LogEntry::class => null` (line 35) to `LogEntry::class => 'team.log.index'` — links to the log list page
   - This fixes the URL filter at `RecordSearchService::globalResultsForDefinition()` line 115 which currently excludes log entries because `urlForModel()` returns empty string

### Frontend

2. Update `SearchOverlay.vue` (`resources/js/components/nav/SearchOverlay.vue`):
   - Add `log_entries` to `SearchResponse` type (lines 31-39)
   - Add `log_entries` to `categories` array (lines 93-105) with icon: `ScrollText` from lucide-vue-next
   - Add `log_entries` to `emptyResults` and `allResults` computed properties
3. Add `g` prefix to `SearchPrefixTooltip.vue` — currently missing from the prefixes array

### Tests

4. Feature test: global search with `g:` prefix returns log entries with correct URL
5. Feature test: unscoped search returns log entries in results

## Notes

- Log entries have no individual detail page (only the list page at `team.log.index`).
- Decision: link search results to the log list page.

## Acceptance Criteria

- [ ] Update `RecordLinkHelper::urlForModel()` to return a URL for `LogEntry` (log list page)
- [ ] Add `log_entries` to `SearchOverlay.vue` (`SearchResponse`, `emptyResults`, `allResults`, `categories`)
- [ ] Add log entry icon to `SearchOverlay.vue` categories
- [ ] Add `g` prefix to `SearchPrefixTooltip.vue` if missing
- [ ] Write feature test for global search returning log entries
