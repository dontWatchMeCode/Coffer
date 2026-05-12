---
id: 4
section: backend
status: todo
severity: low
---

# Extract Shared resolveModel Method

`RecordLinkController` (line 25-35) and `RecordTagController` (line 113-124) have near-identical `resolveModel()` methods. The only difference: `RecordTagController` adds an `instanceof LinkableRecord` type guard.

## Implementation Plan

### Approach

Extract to a shared trait in `app/Concerns/`.

### Current Code

- `RecordLinkController::resolveModel()` (`app/Http/Controllers/RecordLinkController.php:25-35`) — looks up class from `RecordLink::linkableMap()`, queries with `whereBelongsTo($currentTeam)->find()`, returns `?Model`
- `RecordTagController::resolveModel()` (`app/Http/Controllers/RecordTagController.php:113-124`) — identical lookup + query, but adds `instanceof LinkableRecord` type guard at line 123

### Steps

1. Create `app/Concerns/ResolvesLinkableRecord.php` trait with shared method:
   ```php
   public function resolveLinkableRecord(Team $currentTeam, string $type, int $id): ?Model
   ```
   - Lookup class from `RecordLink::linkableMap()` by `$type`
   - Query: `$class::query()->whereBelongsTo($currentTeam)->find($id)`
   - Include `instanceof LinkableRecord` check (take the stricter approach from `RecordTagController`)
   - Return `?Model`
2. Replace `resolveModel()` in `RecordLinkController` (lines 25-35) with `$this->resolveLinkableRecord(...)`
3. Replace `resolveModel()` in `RecordTagController` (lines 113-124) with `$this->resolveLinkableRecord(...)`

## Acceptance Criteria

- [ ] Extract to shared trait or service (e.g. `ResolvesLinkableRecord`)
- [ ] Include the `LinkableRecord` type guard in the shared implementation
- [ ] Both controllers use shared implementation
