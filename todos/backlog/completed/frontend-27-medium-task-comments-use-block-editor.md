---
id: 27
section: frontend
status: done
severity: medium
---

# Use BlockEditor (RTE blocks) for task comments

Task comments currently use `RichTextEditor` (single rich-text `body` field). They should use the same `BlockEditor` setup as the notes edit page, supporting multiple block types (text, excalidraw, mermaid).

## Current State

- `task_comments.body` — `text` column storing rich-text HTML
- `CommentsSection.vue` — uses `RichTextEditor` + `trimStoredRichText` for create/edit/display
- `TaskCommentItem.body` — typed as `string`

## Notes Reference (target pattern)

- Notes use the polymorphic `rte_blocks` table (`blockable_type`, `blockable_id`, `type`, `position`, `payload`) via `morphMany(RteBlock::class, 'blockable')`
- `BlockEditor` component accepts `RteBlock[]` with `{ id, type, position, payload }`
- Notes Show page uses `BlockEditor` in both editable and read-only modes
- `RteBlock` model and table already exist — no new table or model needed

## Acceptance Criteria

- [ ] Update `TaskComment` model with `blocks()` → `morphMany(RteBlock::class, 'blockable')` relationship
- [ ] Update `TaskCommentFactory` to create blocks via `RteBlock` instead of inline body
- [ ] Update `SaveTaskCommentRequest` to validate blocks array instead of body string
- [ ] Update `TaskCommentController::store` and `update` to persist blocks via the polymorphic relationship
- [ ] Update `TaskPageDataService::commentPayload` to include blocks data (load `blocks` relationship)
- [ ] Update `TaskCommentItem` type: replace `body: string` with `blocks: RteBlock[]`
- [ ] Replace `RichTextEditor` with `BlockEditor` in `CommentsSection.vue` for create, edit, and display
- [ ] Remove `RichTextEditor` / `trimStoredRichText` imports from `CommentsSection.vue`
- [ ] Update delete confirmation dialog to use `BlockEditor` for preview
- [ ] Update `AddTaskCommentTool::schema()` to accept `blocks` array instead of `body` string (breaking MCP API change)
- [ ] Update `McpRecordService::addTaskComment` to persist blocks instead of `body`
- [ ] Update `McpRecordService::taskCommentPayload` to return blocks instead of `body` string
- [ ] Update `ListTaskCommentsTool` to reflect new payload shape
- [ ] Create a data migration to convert existing `task_comments.body` HTML into `rte_blocks` records, then make `body` nullable or remove it
- [ ] Update existing tests that create/assert comment body

## Files

- `resources/js/components/pages/tasks/CommentsSection.vue`
- `app/Models/TaskComment.php` — add `blocks()` morphMany relationship
- `app/Models/RteBlock.php` — existing, used via polymorphism
- `app/Http/Controllers/Tasks/TaskCommentController.php`
- `app/Http/Requests/Tasks/SaveTaskCommentRequest.php`
- `app/Services/TaskPageDataService.php`
- `app/Services/McpRecordService.php` — `addTaskComment`, `listTaskComments`, `taskCommentPayload`
- `app/Mcp/Tools/AddTaskCommentTool.php` — schema change from body to blocks
- `app/Mcp/Tools/ListTaskCommentsTool.php`
- `database/factories/TaskCommentFactory.php`
- `resources/js/types/tasks.ts`
- `tests/Feature/Tasks/TaskManagementPageTest.php`
