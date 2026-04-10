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
