---
id: 201
section: backend
status: todo
severity: medium
---

# Filter activity entries at save time

Currently, non-significant activity entries (excalidraw viewport-only changes, empty-to-empty field transitions) are saved to `activity_log` and filtered out at query time via `isSignificantActivityRaw()` in `ProvidesActivityHistory`. This still scans all rows.

## Goal

Skip saving non-significant entries entirely — only persist entries worth showing.

## Approach

1. **Create `app/Services/ActivitySignificance.php`** with a static `isSignificant(?array $attributeChanges, ?array $properties): bool` method. Logic must replicate the pipeline from `ProvidesActivityHistory`:
   - `filterDrawingViewportChanges()` — strips viewport-only excalidraw changes from `$changes['attributes']['drawing_data']` (note: currently dead code — no model logs `drawing_data` as an attribute, but kept for correctness)
   - `filterEmptyFieldChanges()` / `isEmptyActivityFieldValue()` — removes fields where old and new are both empty
   - Check `count($changedFields) > 0` from remaining attributes
   - Check `properties` for `relation_changes` or `block_changes` (tag/link/block activities)
   - All existing `ActivityLogger` entries are already significant by construction, so this is a safety net

2. **Hook into save pipeline** via `Activity::saving` event in `AppServiceProvider::boot()`:
   - Call `ActivitySignificance::isSignificant()`, return `false` to prevent save
   - This catches: model updates via `LogsActivity` trait, manual `activity()->log()` calls, `ActivityLogger` service calls

3. **Simplify queries** in `ProvidesActivityHistory`:
   - Remove `getSignificantActivities()` — no longer needed
   - `paginatedActivityHistoryPayload()` can use direct DB-level pagination with `where('subject_type', ...)->where('subject_id', ...)->orderByDesc('id')->paginate($perPage)` — no PHP-side significance check needed (all stored rows are significant)
   - `activityHistoryConfig()` can use `Activity::where(...)->count()`
   - `activityHistoryPayloadForModels()`: keep `isSignificantActivity()` filter temporarily as safety, remove after migration verified
   - `isSignificantActivity(array)` can be removed after migration complete (used only by `activityHistoryPayloadForModels`)

4. **Backfill** — one-time Artisan command:
   - Iterate all `activity_log` rows, check significance using same `ActivitySignificance::isSignificant()`
   - Delete non-significant rows

## Potential risks

- `drawing_data` viewport-stripping in `filterDrawingViewportChanges()` is currently dead code (no model logs `drawing_data` as a tracked attribute). The significance service should still include it for correctness, but it won't affect current data
- `activityHistoryPayloadForModels()` still loads all activities — it can be optimized separately if needed

