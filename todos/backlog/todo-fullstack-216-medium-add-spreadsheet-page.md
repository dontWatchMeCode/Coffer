---
id: 216
section: fullstack
status: todo
severity: medium
---

# Add Spreadsheet-Like Page

Add a spreadsheet-style record page where teams can view data in a table with user-defined columns.

## Scope

- Grid/table view with sortable, resizable, and hideable columns
- Users can add, name, reorder, and remove custom columns
- Column types: text, number, date, select, checkbox, etc.
- Inline cell editing
- Rows as team-scoped records with standard features (tags, links, activity)
- Persist column schema per team (or per user within team)
- Export to CSV

## Acceptance Criteria

- [ ] Define column schema storage (per-team or per-user)
- [ ] Implement spreadsheet data model (rows + user-defined columns)
- [ ] Build table UI with drag-reorderable columns
- [ ] Add column editor (add, name, type, reorder, delete)
- [ ] Support inline cell editing
- [ ] Wire team-scoped authorization, tags, links, activity history
- [ ] Add global search support
- [ ] Add CSV export
- [ ] Register in sidebar navigation
