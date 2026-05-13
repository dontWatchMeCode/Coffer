---
id: 20
section: frontend
status: planning
severity: medium
---

# Plan Mobile Layout Fixes

Audit and improve mobile layouts across the app.

## Current Implementation Notes

- Record pages use several layout patterns, including list/detail pages and `EditorSidebarLayout` detail pages.
- Some pages use desktop-first sidebars, sticky panels, grids, and wide action rows that need mobile review.
- This is broad enough to require an audit before implementation.

## Planning Work

1. Audit all main pages on small viewports: dashboard, tasks, calendar, contacts, bookmarks, subscriptions, notes, log, collections, team settings, and API tokens.
2. Identify layout breakpoints where sidebars, action bars, tables, dialogs, and editors overflow.
3. Group fixes by shared layout/component first, then page-specific fixes.
4. Define a minimal smoke-test path for mobile navigation and core record CRUD.

## Acceptance Criteria

- [ ] Mobile audit notes exist for every main app area
- [ ] Shared layout fixes are preferred over page-specific patches
- [ ] Record list pages are usable on narrow screens
- [ ] Record detail pages are usable on narrow screens
- [ ] Dialogs and editor surfaces avoid horizontal overflow
- [ ] Mobile smoke coverage or documented manual QA checklist exists
