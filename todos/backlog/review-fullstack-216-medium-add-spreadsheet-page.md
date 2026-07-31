---
id: 216
section: fullstack
status: review
severity: medium
---

# Add Spreadsheet-Like Page

Add a minimal spreadsheet-style area where teams can manage flexible tabular data without introducing a licensed grid dependency.

## Decision

Handsontable is technically suitable, but commercial production use requires a paid proprietary license. The implemented native Vue grid keeps the feature dependency-free and aligned with the existing UI.

## Scope

- Multiple team-scoped workbooks stored as validated JSON snapshots
- Grid view with sortable, resizable, hideable, and drag-reorderable columns
- Users can add, rename, configure, reorder, and remove custom columns
- Column types: text, number, date, shadcn Select, and shadcn Checkbox; cleared selects persist as `null`
- Inline cell editing with in-flight save edits preserved
- Confirmed row and column deletion; row deletion replaces the row number on hover and dialogs dismiss outside
- Full-width matching header background without changing table width; resizing cannot trigger column reordering
- Manual workbook saves with a 2 MB snapshot limit
- Workbooks use standard team record features: tags, links, activity, trash, and restore
- Workbook title/tag filtering and global search with the `x:` prefix
- Export to CSV

Rows are intentionally snapshot data rather than separate linkable/taggable records. Standard record integration applies at workbook level for this minimal version.

## Acceptance Criteria

- [x] Define team-scoped workbook and column schema storage
- [x] Implement snapshot data model for rows and user-defined columns
- [x] Build sortable, resizable, hideable, drag-reorderable table UI
- [x] Add column editor for name, type, options, reorder, and delete
- [x] Support inline cell editing and row management
- [x] Wire team-scoped authorization and workbook-level tags, links, activity, and trash
- [x] Add title/tag filtering and global search support
- [x] Add safe CSV export
- [x] Register in sidebar navigation

## Verification

- `composer qa`
- Spreadsheet browser tests: 4 passed, 19 assertions
- Spreadsheet feature tests: 9 passed, 77 assertions, including cross-team trash actions
- Six-pass QA review loop completed cleanly
