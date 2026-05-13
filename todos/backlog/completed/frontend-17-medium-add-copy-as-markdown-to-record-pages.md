---
id: 17
section: frontend
status: done
severity: medium
---

# Add Copy as Markdown to Record Pages

Add a `Copy as Markdown` action to each main record page so users can copy a portable Markdown representation of the current record.

## Pages

- Tasks
- Calendar
- Contacts
- Bookmarks
- Subscriptions
- Notes
- Log
- Collections

## Current Implementation Notes

- Record pages are Inertia Vue pages under `resources/js/pages/*`.
- Detail pages already use shared panels such as `EditorSidebarLayout`, `RecordTagsPanel`, `RecordLinksPanel`, and `ActivityHistoryPanel`.
- Task comments live in `TaskComment` / `CommentsSection`; the Tasks Markdown output should include comments.
- No app-level copy-as-Markdown component currently exists outside compiled framework views.

## Implementation Plan

1. Add a small shared frontend copy action/component that uses `navigator.clipboard.writeText()` and shows copied/error state.
2. Add per-record Markdown serializers near the pages or in a shared frontend helper, keeping field names consistent with current page labels.
3. Include tags and linked records where those props are already loaded on the detail page.
4. Include Task comments in the Tasks serializer, ordered the same way as the task detail page displays them.
5. Add the action to each page in an existing action area rather than introducing a new layout pattern.

## Acceptance Criteria

- [ ] Tasks can be copied as Markdown, including task comments
- [ ] Calendar events can be copied as Markdown
- [ ] Contacts can be copied as Markdown
- [ ] Bookmarks can be copied as Markdown
- [ ] Subscriptions can be copied as Markdown
- [ ] Notes can be copied as Markdown, including text or Excalidraw metadata/placeholder
- [ ] Log entries can be copied as Markdown
- [ ] Collections can be copied as Markdown
- [ ] Copy action uses existing button/toolbar styling
- [ ] Clipboard success and failure states are visible
- [ ] Add frontend or feature coverage for at least one serializer plus smoke coverage for every page action
