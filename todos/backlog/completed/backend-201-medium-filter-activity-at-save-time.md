---
id: 201
section: backend
status: done
severity: medium
---

# Filter activity entries at save time

Currently, non-significant activity entries (excalidraw viewport-only changes, empty-to-empty field transitions) are saved to `activity_log` and filtered out at query time via `isSignificantActivityRaw()` in `ProvidesActivityHistory`. This still scans all rows.

## Goal

Skip saving non-significant entries entirely — only persist entries worth showing.

## Approach

1. **Create `app/Services/ActivitySignificance.php`** with a static `isSignificant(?array $attributeChanges, ?array $properties): bool` method and a public `filterAttributeChanges(array $changes): array` method. Logic replicates the pipeline from `ProvidesActivityHistory`:
   - `filterDrawingViewportChanges()` — strips viewport-only excalidraw changes from `$changes['attributes']['drawing_data']`
   - `filterEmptyFieldChanges()` / `isEmptyActivityFieldValue()` — removes fields where old and new are both empty
   - Check `$changedFields !== []` from remaining attributes
   - Check `properties` for `relation_changes` or `block_changes` (tag/link/block activities)
   - `filterAttributeChanges()` is public so `ProvidesActivityHistory::buildActivityItem()` can reuse it for item rendering

2. **Hook into save pipeline** via `LogActivityAction::save()` override:
   - `LogActivityAction` extends Spatie's `LogActivityAction`, overrides `save()` to check `ActivitySignificance::isSignificant()` before calling `parent::save()`
   - If non-significant, `save()` returns early — activity is never persisted
   - Catches model updates via `LogsActivity` trait and manual `activity()->log()` calls

3. **Simplify queries** in `ProvidesActivityHistory`:
   - Removed `getSignificantActivities()` — no longer needed
   - `paginatedActivityHistoryPayload()` uses direct DB-level pagination with `where('subject_type', ...)->where('subject_id', ...)->orderByDesc('id')->paginate($perPage)` — no PHP-side significance check needed (all stored rows are significant)
   - `activityHistoryConfig()` uses `Activity::where(...)->count()`
   - Removed `isSignificantActivity(array)` entirely
   - Removed private helpers (`filterDrawingViewportChanges`, `filterEmptyFieldChanges`, `isEmptyActivityFieldValue`, `drawingDataEqualsIgnoringViewport`) — migrated to `ActivitySignificance`

4. **Backfill** — Skipped (app is testing-only, not deployed). No stale non-significant rows exist in production. No cleanup migration or Artisan command created.

## Deviations from original plan

- **Save hook location**: Overrode `LogActivityAction::save()` instead of `Activity::saving` event in `AppServiceProvider`. More correct because `LogActivityAction` is Spatie's extension point; `saving` event fires too late or inconsistently in this flow.
- **`filterAttributeChanges()` made public**: Needed by `ProvidesActivityHistory::buildActivityItem()` for rendering; the todo didn't anticipate this.
- **No temporary safety filter**: `activityHistoryPayloadForModels()` had its `isSignificantActivity()` filter removed outright since no backfill is needed (no stale data to guard against).
- **No backfill command**: Explicitly skipped per user instruction.

## Potential risks

- `drawing_data` viewport-stripping in `filterDrawingViewportChanges()` is currently dead code (no model logs `drawing_data` as a tracked attribute). The significance service still includes it for correctness, but it won't affect current data
- `activityHistoryPayloadForModels()` still loads all activities — it can be optimized separately if needed
