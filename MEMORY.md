# Memory

## Tasks / Projects Page Notes

### Main Issues Hit

1. Wayfinder route params were not passed from Vue forms.

- Result: requests went to paths like `/$currentTeam/tasks/projects` and returned `404`.
- Fix: always pass `current_team` explicitly into Wayfinder form helpers on team-scoped pages.

2. Mixed route import paths caused frontend runtime errors.

- Result: browser error `doesn't provide an export named: 'show'`.
- Cause: some files imported `@/routes/team/tasks`, others imported `@/routes/team/tasks/index`.
- Fix: use one consistent import path for generated Wayfinder route modules.

3. Detail page was initially kept inside the overview page.

- Result: too much state/UI branching in one Vue file and awkward navigation.
- Fix: split overview and detail into separate pages/routes early.

4. Redirect targets did not match the final UX.

- Result: updates returned users to the overview instead of the selected project detail page.
- Fix: once detail routes exist, task/project update actions should redirect back to the selected project page.

5. Generated route/module assumptions were trusted before checking browser logs.

- Result: time spent debugging the backend when the actual error was a frontend module import/runtime mismatch.
- Fix: when a page fails to load, check browser logs first.

6. Controller/page props changed but tests still asserted the old shape.

- Result: task page tests failed after removing stats from the detail page.
- Fix: update feature tests immediately when page props/layout responsibilities change.

7. Shared overview/detail controller logic became overly clever.

- Result: `TaskPageController` briefly grew unnecessary complexity and type issues.
- Fix: prefer simple duplicated query/setup over abstracting too early when the page split is still moving.

### What To Do Next Time

1. For team-scoped Inertia pages:

- always pass `current_team` into Wayfinder route/form calls
- do not rely on placeholder defaults like `$currentTeam`

2. For generated route imports:

- pick one import style and keep it consistent across the app
- after route changes, run `composer run qa` or at least `php artisan wayfinder:generate --with-form`

3. For page architecture:

- use separate routes/pages for overview vs. detail flows when selection changes the whole layout
- avoid embedding a detail workspace behind local component state if it deserves its own URL

4. For debugging:

- browser logs first for page-load issues
- request history next for bad URLs or 404s
- backend logs/errors after that

5. For tests:

- add/adjust feature tests whenever route structure, redirect targets, or Inertia props change
- test both overview page render and detail page render once routes are split

6. For controllers:

- keep prop-shaping helpers simple and typed
- avoid clever transformations just to reuse one small stat or mapping helper

### Good Patterns Confirmed

1. Overview page: `/{current_team}/tasks`
2. Detail page: `/{current_team}/tasks/{project}`
3. Overview page should be project cards + create-project modal
4. Detail page should contain project edit + create task + task editing
5. `composer run qa` is the right final verification step because it refreshes Wayfinder output too

### Session: UI Refactoring (Apr 2026)

1. `DialogTrigger as-child` wrapping a `Button` can strip `cursor-pointer`.
    - Fix: add explicit `cursor-pointer` class to buttons inside DialogTrigger.

2. When moving DOM elements (e.g. a Badge) during refactoring, orphaned elements cause `vue/no-parsing-error`.
    - Fix: always verify parent/wrapper divs are properly closed after moving children.

3. `defineProps()` without assigning to a variable causes `ReferenceError: props is not defined` when used in computed properties.
    - Fix: always `const props = defineProps<Props>()`.

4. Conditionally rendered elements (`v-if`) inside cards cause height shifts when toggling filters.
    - Fix: use `invisible` class instead of `v-if` to preserve layout space.

5. Theme `bg-primary` may match surrounding colors (e.g. dark theme primary is near-black).
    - Fix: use explicit colors (`bg-black`, `bg-white`) with dark mode variants when you need guaranteed contrast.

### Session: Hardening & Polish (Apr 2026)

1. Form request `authorize()` called `$this->route('current_team')` directly without null-checking before passing to `whereBelongsTo()`.
    - Fix: extract a `currentTeam(): ?Team` helper in the trait and guard with `instanceof Team`.
    - Pattern: centralize route-model resolution in a shared trait method so every authorize/rules call is safe.

2. `Task::syncCompletionTimestamp` read `$task->status` which hit the enum cast and always returned the casted object, making dirty-check comparison unreliable.
    - Fix: read raw attribute via `$task->getAttributes()['status'] ?? $task->getRawOriginal('status')` before enum casting.
    - Pattern: when checking dirty state against enum casts, read the raw attribute, not the accessor.

3. Escape key in `PageHeader` navigated back even when a popover/dialog/dropdown was open.
    - Fix: check `event.target` against `[data-radix-popper-content-wrapper]`, `[role="dialog"]`, `[data-state="open"]` before navigating.
    - Pattern: always check for open overlay elements in global keyboard handlers.

4. `completed_at` was in `#[Fillable]` but is managed entirely by the model's `saving` hook.
    - Fix: remove from fillable to prevent accidental mass-assignment bypassing the status-sync logic.

5. Index page lacked an authorization test — only show and edit were covered.
    - Fix: add a test asserting non-team-members get forbidden on the task index route.

6. Progress slider guard compared against already-updated local value instead of server value.
    - Fix: read server value fresh in guard closure via `taskForm.progress`.

7. TaskController::update redirected to /edit path regardless of Referer.
    - Fix: redirect back when Referer doesn't contain /edit (list view stays on list view after status/progress change).

8. Nested interactive elements in list view (Link wrapping Select).
    - Fix: replace Link wrapper with `div[role="link"]` + keyboard handler for accessibility.

9. TooltipProvider was rendered per-row around status Select.
    - Fix: hoist TooltipProvider to wrap entire task list; use TooltipTrigger to wrap the Select.

10. SelectTrigger lacked customization for icon visibility and hover style.
    - Fix: add `hideIcon` (removes chevron) and `hoverPrimary` (hover:bg-primary) props with transition-colors.

### Session: QA Pass (Apr 2026)

1. Added `hideIcon` to `reactiveOmit` in SelectTrigger.vue to prevent leaking non-standard HTML attribute to DOM.
2. Added Space key support (`@keydown.enter.space.prevent`) to div[role="link"] in Show.vue task list for keyboard accessibility.

### Session: Calendar & Contacts Features (Apr 2026)

1. **Wayfinder `applyUrlDefaults` is dead code** — `setUrlDefaults()` is never called in this project, so `$currentTeam` placeholder in generated route functions does not auto-fill.
    - Fix: always pass `currentTeamSlug` explicitly in every route call (e.g. `storeContact.form(currentTeamSlug)`).

2. **shadcn Dialog uses portals** — DOM order doesn't control z-index between stacked dialogs.
    - Fix: close the background dialog before opening the foreground one (e.g. close edit dialog before opening delete dialog).

3. **SQLite doesn't auto-index FK columns** — `foreignId()->constrained()` creates an implicit index on MySQL/PostgreSQL but not SQLite.
    - Fix: add explicit `$table->index('team_id')` for SQLite STRICT branch; Blueprint branch doesn't need it.

4. **SQLite stores dates as datetime strings** (`"2026-04-20 00:00:00"`).
    - Fix: `assertDatabaseHas` date comparisons need to account for this format.

5. **Team model uses `SoftDeletes`** — cascade delete tests must use `forceDelete()` not `delete()`.

6. **Laravel validation rules cannot be concatenated as strings** — `"sometimes|required"` is treated as a single rule name, not two rules.
    - Fix: use array format: `['sometimes', 'required', 'string']`.

7. **Vue `useForm` vs `<Form>` component for dynamic arrays** — `<Form>` with `name` attributes works for simple fields but not for dynamic array fields (phone_numbers, email_addresses) managed via `v-model`.
    - Fix: use `router.post()`/`router.patch()` directly with reactive refs, display errors from `usePage().props.errors`.

8. **Vue `v-for` keys with duplicate values** — using raw string values as `:key` breaks DOM reconciliation when values repeat across different arrays (emails + phones).
    - Fix: use index as key when the source data may have duplicates: `:key="idx"`.

9. **`php artisan route:list` fails with ReflectionException** if a controller referenced in routes can't be autoloaded.
    - Fix: always verify `use` statements are present in `routes/web.php` before running route commands.

10. **JSON columns for multi-value fields** — use `phone_numbers`/`email_addresses` as JSON arrays of `{label, value}` objects rather than separate tables.
    - Pattern: validate with `array|max:20` on parent, per-entry `label` (max:100, nullable) and `value` (email/string, nullable).
    - Pattern: use `protected function casts(): array` with `'phone_numbers' => 'array'`.
    - Pattern: filter empty entries on the frontend before sending to backend.

11. **Migration altering columns in SQLite** — SQLite doesn't support `MODIFY COLUMN` or `DROP COLUMN`.
    - Fix: recreate the table under a new name, copy data with SQL expressions (e.g. `json_extract`), drop old table, rename new one.

### Session: Record Links Feature (Apr 2026)

1. **Normalized pair storage prevents duplicates** — `record_links` table stores `(left_type, left_id, right_type, right_id)` with `left <= right` ordering (by FQCN then ID), plus a unique index on the 5-tuple with `team_id`.
2. **Race condition on concurrent link creation** — `exists()` check + `create()` is not atomic. Wrap `create()` in `try/catch` for `QueryException` with SQLSTATE `23000` to return 422 instead of 500.
3. **Type alias mismatch between frontend and backend** — controller expected `'Contact'` but `recordLinkContext()` returned `'contact'`. Added `typeAliasFor()` centralizing lowercase aliases.
4. **Project candidate search SQL error** — `Project` uses `name` not `title`. Added explicit `Project::class` cases to search fields and `orderBy` in `candidates()`.
5. **Cross-type ID collision on disabled states** — Vue `addingId`/`removingId` used raw integer IDs, so a Task with ID 5 and a Contact with ID 5 would share state. Changed to composite key `${type}-${id}`.
6. **Stale CSRF token on `router.reload()`** — `<meta name="csrf-token">` is not updated by Inertia `router.reload()`. Read `XSRF-TOKEN` cookie instead, with meta tag fallback.
7. **DELETE with JSON body fails on some proxies/WAFs** — switched to query params for `destroy()` endpoint; updated frontend to append `?from_type=...` to URL.
8. **Orphan `RecordLink` rows on model deletion** — added `bootHasRecordLinks()` with `static::deleting` event that calls `RecordLink::queryForModel(...)->delete()`.
9. **PHPStan level 8 trait method generics** — `array<int, T>` PHPDoc on trait methods analyzed in model context triggers `missingType.iterableValue`. Use `array<T>` (list shorthand) instead.
10. **Shared TypeScript types across 7 Vue files** — extracted `LinkRecord`, `LinkContext`, `LinkEndpoints` to `resources/js/types/record-links.ts` to prevent drift.
11. **`formattedLinkedRecords()` requires `Team` param** — made `$currentTeam` required to avoid N+1 `Team::find()` fallback and null-team crashes.
12. **Task URL breaks when `project_id` is null** — guard `routeName` to `null` when `project_id` is missing so `route()` is never called with null params.

### Session: Collections Feature (Apr 2026)

1. **Avoid `Collection` as an Eloquent model name** — Laravel's collection class makes that name confusing.
    - Fix: use `RecordCollection` as the model and `record_collections` as the table while keeping routes/UI named `Collections`.

2. **New linkable record types require multi-surface registration** — adding Collections required updates to `RecordLink::linkableMap()`, `RecordLinkHelper`, `SearchPrefixes`, global search, sidebar nav, and Wayfinder-generated routes.
    - Pattern: add focused tests for CRUD, search prefix, tagging, linking, and backlinks when introducing a new record type.

3. **Linked-record previews are payload-level behavior** — adding `preview` to `formattedLinkedRecords()` keeps existing sidebar lists compatible while allowing richer collection pages.
    - Fix: assert preview fields in record-link tests so future link payload changes do not silently drop collection previews.

4. **Collection show page should reuse the shared edit layout** — keeping tags and link management in `EditorSidebarLayout` matches the other record pages and avoids a separate interaction model.

### Session: Shared List Components (May 2026)

1. **Extracted reusable list UI components** to `resources/js/components/list/`:
    - `ListContainer.vue` — layout container (list/grid toggle)
    - `ListItem.vue` — clickable card with keyboard accessibility (Enter/Space)
    - `ListItemIcon.vue` — icon wrapper
    - `ListItemActions.vue` — hover-reveal action buttons
    - `EmptyState.vue` — shared empty state with optional action

2. **Refactored pages to use shared components**:
    - Contacts (`ContactList.vue`)
    - Bookmarks (`BookmarkList.vue`)
    - Notes (`pages/notes/Index.vue`)
    - Collections (`pages/collections/Index.vue`)
    - Calendar list view (`CalendarList.vue`)

3. **Extracted `formatDate` and `formatDateTime`** to `resources/js/lib/utils.ts` for reuse across components.

4. **Bug fixes during QA**:
    - `CalendarList` — unsafe date parsing fixed
    - `EmptyState` — `actionLabel` default value added
    - `ListItem` — click guard for disabled state
    - `ListItem` — keyboard accessibility (Enter/Space on div[role="link"])
    - `ListItem` — `clickable` guard added when `link.url` is falsy (prevents click on disabled items)
    - `ListItem` — `aria-label` binding fixed to avoid "undefined" string in DOM
    - Collections delete button — added `destructive` styling
    - `EmptyState` — `description` prop made optional

5. **Collections page style alignment**:
    - Index page refactored to match Contacts style (entire card clickable, horizontal list layout)
    - Show page refactored to match Contacts show page style
    - Edit form added cancel button with proper state reset

6. **Linked records section** — `border-t` restored on the linked records section for visual separation.

### Session: View Mode Toggle & QA Loop (May 2026)

1. **Created `useViewMode` composable** (`resources/js/composables/useViewMode.ts`) for shared list/card view mode state persisted to `localStorage` (`app-list-view-mode`).

2. **Created `ViewModeToggle` component** (`resources/js/components/list/ViewModeToggle.vue`) to deduplicate toggle UI across pages.

3. **Added view mode toggle to all list pages**: Collections, Notes, Contacts, Bookmarks.
    - Grid view: card layout with icon, title, description, tags, updated date
    - List view: horizontal row with icon, title, description, actions
    - All pages share the same `localStorage` setting

4. **Removed edit buttons** from Contacts and Bookmarks list items (navigation is via card click).

5. **Bug fixes during QA loop**:
    - `ListItem.vue` — fixed keyboard event bubbling (`event.target !== event.currentTarget` guard) so inner buttons don't trigger card navigation
    - `BookmarkList.vue` — fixed invalid HTML nesting (`<a>` wrapping `<Button>` → `Button as="a"`)
    - `collections/Show.vue` — same `<a>`/`<Button>` nesting fix for external links
    - `BookmarkList.vue` — added missing `aria-label` on external-link buttons
    - `notes/Index.vue` — added missing `aria-label` on delete buttons
    - `collections/Show.vue` — added missing `aria-label` on external-link button
    - `ContactList.vue` — extracted `filterEntries` helper to remove duplicated `.filter((e) => e.value.trim())`
    - `contacts/Index.vue` & `bookmarks/Index.vue` — removed duplicate list component rendering (render list once, conditionally show toggle)

6. **Refactors**:
    - Reverted `EditorSidebarLayout.vue` null guard simplification — Vue TSC doesn't understand computed narrowing in templates
    - Removed unused `List`/`LayoutGrid` imports from index pages after extracting `ViewModeToggle`
    - `ViewModeToggle.vue` — switched to `defineModel` for cleaner v-model API
    - `ListContainer.vue` — changed default layout from `'list'` to `'grid'` to match `useViewMode` default
    - `ContactList.vue` — extracted `buildContactInfo` helper for shared contact info rendering

7. **Additional QA loop fixes (passes 4–10)**:
    - `CalendarList.vue` — fixed `isToday` comparison to handle ISO datetimes correctly
    - `CalendarList.vue` — hoisted `todayDateOnly` computation outside event loop
    - `CalendarList.vue` — used normalized `date` variable consistently instead of raw `event.date`
    - `CalendarList.vue` — added `data-testid` attributes for testing consistency
    - `CalendarList.vue` — removed redundant `description=""` from EmptyState
    - `CalendarList.vue` — added event sorting within month groups
    - `ContactList.vue` — added missing "No contact info yet" fallback in list view
    - `useViewMode.ts` — hoisted `sharedViewMode` ref to module scope so all pages share state
    - `useViewMode.ts` — added `typeof window` guards for SSR safety
     - `ListItem.vue` — fixed `@keydown` handler (`props.clickable && handleKeydown` returned function ref but never called it)

### Session: MCP API Token Feature (May 2026)

1. **McpToken model split into two migrations**: `create_mcp_tokens_table` has the hash, abilities, timestamps. A second migration `add_token_column_to_mcp_tokens_table` adds the encrypted `token` column — ensures hash is available before the plaintext token is stored.

2. **Token never stored in plaintext log/history**: `token` column uses `encrypted` Eloquent cast. Auth is done via `token_hash` (SHA-256), not the plaintext value. The plaintext `token` is shown once on create and exposed to the owner for copy.

3. **`forceFill` used in `createToken`** — `forceFill` bypasses `#[Fillable]` because `user_id`, `team_id`, `token_hash`, `token`, and `abilities` are all set in a single factory method; only `name`, `abilities`, and `expires_at` are in fillable.

4. **Owner-only edit/revoke** — `ApiTokenController::update` and `destroy` check both `team_id` and `user_id` match the authenticated user, returning 404 if mismatched.

5. **Permission model per resource type** — `McpToken::allows()` reads abilities JSON for the mapped resource key (`task` maps to `tasks`, `calendar_event` maps to `calendar`). Three levels: `none` (deny read+write), `read` (allow read only), `write` (allow read+write).

6. **Task project scoping** — `ability.task_projects` supports `mode: 'all'` (no restriction) or `mode: 'only'` with `ids: [project IDs]`. `StoreApiTokenRequest` validates the structure; `ApiTokenController::abilities()` validates project IDs exist in the team. `McpTokenPermissionService` passes `projectId` context to `allows()`.

7. **MCP middleware vs web auth are separate paths** — `AuthenticateMcpToken` looks up token by hash, resolves `user` and `currentTeam` from the token, binds `McpToken::class` to the container. It does NOT use session/cookie auth. After request: Auth resolver and container binding are restored to original.

8. **Permission check integration in MCP service** — `McpRecordService` calls `McpTokenPermissionService::can()` before every operation, and `McpTokenPermissionService::readableTypes()` filters schema/search results. If no token is bound (web UI context), all operations are allowed (gate + team scoping still apply).

9. **Frontend: api-tokens/Index.vue** — Full create/edit modal with:
    - Per-resource read/write/none selectors
    - Task project scope: all-projects vs selected-projects via tags-input + listbox
    - Copy button on every card (2s Copied state)
    - Edit button to modify name/expiry/permissions
    - Revoke button to delete

10. **TypeScript types** — `ApiTokenAbilities`, `ApiTokenItem`, `ApiTokenProject`, `ApiTokenPermission` in `resources/js/types/api-tokens.ts`, re-exported from `index.ts`.

11. **User menu nav** — "MCP" entry in `UserMenuContent.vue` linking to the team MCP token page.

### Session: Activity History Feature (May 2026)

1. **Spatie activitylog integration** on Note model with `logOnly`, `logOnlyDirty`, `dontLogEmptyChanges`.
    - Created reusable `ProvidesActivityHistory` trait for building Inertia payloads.
    - Filtered viewport-only `drawing_data` changes (scrollX/Y/zoom) so panning doesn't log false updates.

2. **ActivityHistoryPanel Vue component** — Dialog modal showing event, timestamp, user, changed fields.
    - Drawing fields: Before/After toggle buttons with active-state styling.
    - Text fields: word-level diff via `diff` npm package in `TextDiff.vue`.
    - Toggles reset when dialog closes via `watch(open)`.

3. **Excalidraw focus/escape fixes**:
    - Scoped Escape handler to overlay div (`@keydown.esc.stop.prevent`) instead of global document listener.
    - Save/restore `document.activeElement` when opening/closing overlay so focus returns to Dialog (prevents PageHeader back-navigation bug).

4. **UI layout adjustments**:
    - Reordered sidebar: Updated at above Activity History with reduced spacing.
    - Removed outer card border from notes view mode for both text and excalidraw.

5. **QA loop findings**:
    - `TextDiff.vue` missing `computed()` wrapper caused stale diffs on prop changes.
    - `getOldValue`/`getNewValue` duplicated logic — consolidated to `getFieldValue(activity, field, side)`.
    - `stripSelectionState` double-cloned appState — removed inner `cloneJson`, used shallow spread.
    - `drawingDataEqualsIgnoringViewport` originally mutated arrays via `unset` — refactored to pure closure with `appState` guard.
