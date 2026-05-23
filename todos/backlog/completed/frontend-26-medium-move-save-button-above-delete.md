---
id: 26
section: frontend
status: done
severity: medium
---

# Move editor/save button above delete button in sidebar

The `EditorSidebarLayout` sidebar currently renders actions (save/copy-as-markdown/delete) at the bottom of the sidebar. Pages with inline save buttons below the content (notes Show, calendar Edit, bookmarks Show, contacts Show, collections Show, subscriptions Show) should instead move the save action to the sidebar, positioned above the delete button.

## Acceptance Criteria

- [ ] For each page using `EditorSidebarLayout`, remove the inline save/Edit button pair from the main content area
- [ ] Wire up the `onSave` prop on `EditorSidebarLayout` in all pages that currently have an inline save
- [ ] Verify save button renders above delete in the sidebar actions block
- [ ] Ensure edit mode toggling still works (entering/exiting edit mode)

## Affected Pages

- `resources/js/pages/notes/Show.vue` — has inline "Edit" + "Save changes"
- `resources/js/pages/calendar/Edit.vue` — has inline "Edit" + "Save changes"
- `resources/js/pages/bookmarks/Show.vue` — has inline "Edit" + "Save changes"
- `resources/js/pages/contacts/Show.vue` — has inline "Edit" + "Save changes"
- `resources/js/pages/collections/Show.vue` — has inline "Edit" + "Save changes"
- `resources/js/pages/subscriptions/Show.vue` — has inline "Edit" + "Save changes"

## Files

- `resources/js/components/layouts/EditorSidebarLayout.vue` — already renders save above delete, may need minor adjustments
- Each Show/Edit page listed above
