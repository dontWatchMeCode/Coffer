---
id: 18
section: frontend
status: done
severity: high
---

# Plan Block Style Notes Editor

Replace the current note-level TipTap/Excalidraw format switch with a block style editor for Notes.

## Goal

Notes should support multiple blocks in a single note:

- TipTap rich text blocks
- Excalidraw drawing blocks

File and image blocks deferred to a future phase. Phase 1 scoped to text + excalidraw only.

## Current Implementation

- `resources/js/pages/notes/Show.vue` switches the whole note between `format: text` and `format: excalidraw`.
- Text editing uses `RichTextEditor`, stores Markdown from TipTap.
- Drawings use `ExcalidrawEditor` and `notes.drawing_data` JSON field.
- `notes` table has `body` (text), `format` (varchar), `drawing_data` (json) — no multi-block structure.
- No existing file upload or media/attachment infrastructure.

## Decisions

| Decision | Choice |
|---|---|
| Storage | Generic `rte_blocks` table with polymorphic `blockable` relationship |
| Block types (phase 1) | `text` (Markdown), `excalidraw` (drawing JSON) |
| Text block payload | Markdown string in `payload.content` |
| Excalidraw block payload | `ExcalidrawScene` JSON in `payload.scene` |
| File/image blocks | Deferred to future phase |
| Reorder UX | Up/down buttons per block (no drag-and-drop) |
| Excalidraw UX | Inline embedded canvas within block list |
| Insertion UX | "Add block" buttons (minimal viable) |
| Migration/backfill | None — site is in testing phase; drop old columns |
| Old columns | Drop `body`, `format`, `drawing_data` from `notes` in same migration |

## Database Schema

### New: `rte_blocks` table

```sql
rte_blocks
  id            bigint PK
  blockable_type varchar         -- e.g. 'App\Models\Note'
  blockable_id   bigint          -- FK morph
  type           varchar         -- 'text', 'excalidraw'
  position       integer         -- ordering within parent
  payload        json            -- type-specific data
  created_at     timestamp
  updated_at     timestamp

indexes:
  blockable (blockable_type, blockable_id)
  position per blockable (for ordering queries)
```

### `payload` shape per type

**`text` block:**
```json
{
  "content": "# Heading\n\nSome **markdown** text."
}
```

**`excalidraw` block:**
```json
{
  "scene": {
    "type": "excalidraw",
    "version": 2,
    "source": "...",
    "elements": [...],
    "appState": {...},
    "files": {...}
  }
}
```

### Migration: alter `notes` table

Drop columns: `body`, `format`, `drawing_data`.

## Backend Implementation

### Model: `RteBlock`

- `blockable()` morphTo
- Fillable: `blockable_type`, `blockable_id`, `type`, `position`, `payload`
- Cast: `payload` => `array`
- Scope: `ordered()` sorts by `position` ASC

### Model: `Note` changes

- Remove `body`, `format`, `drawing_data` from fillable
- Remove `drawing_data` cast
- Remove the `saving` boot hook that nulls body/drawing_data
- Add `blocks()` morphMany (`RteBlock::class`, ordered by position)
- Update activity log to log blocks or omit old fields
- Add `syncBlocks(array $blocks): void` method — handles create/update/delete/reorder of blocks in one call

### Controller changes

- `NotePageController::show` — eager-load `blocks`, pass to Inertia
- `NoteController::update` — accept `blocks` array instead of `body`/`format`/`drawing_data`
- `SaveNoteRequest` — validate `blocks` as array of `{type, position, payload}`, remove old field rules

### `syncBlocks` logic

```php
// Accept array of blocks with {id?, type, position, payload}
// - Blocks with existing ID: update type/position/payload
// - Blocks without ID: create
// - Existing blocks not in the array: delete
// - Wrap in a transaction
```

## Frontend Implementation

### New types (`resources/js/types/blocks.ts`)

```ts
type RteBlockType = 'text' | 'excalidraw';

type RteBlock = {
  id: number;
  type: RteBlockType;
  position: number;
  payload: TextPayload | ExcalidrawPayload;
};

type TextPayload = { content: string };
type ExcalidrawPayload = { scene: ExcalidrawScene };
```

### New components

- `BlockEditor.vue` — container managing ordered list of blocks, add-block buttons, up/down/delete per block
- `TextBlock.vue` — wraps `RichTextEditor` for a single text block
- `ExcalidrawBlock.vue` — wraps `ExcalidrawEditor` for a single drawing block (inline)

### `Show.vue` changes

- Replace the format toggle and single editor with `BlockEditor`
- Pass `blocks` array from page props
- On save, POST/PATCH the full blocks array to the update endpoint
- Keep existing tags, links, activity sidebar layout untouched

### Block editor UX

- Each block rendered in order with a thin toolbar (move up, move down, delete)
- "Add text block" / "Add drawing block" buttons at the bottom of the block list
- Text blocks: inline TipTap editor
- Excalidraw blocks: inline embedded canvas with expand/collapse (reuse existing expand behavior)
- View mode: render text blocks as readonly HTML, excalidraw blocks as static preview

## Updated Acceptance Criteria

- [ ] `rte_blocks` table created with polymorphic blockable relationship
- [ ] `body`, `format`, `drawing_data` columns dropped from `notes`
- [ ] `RteBlock` model with `blockable` morphTo and `ordered` scope
- [ ] `Note` model has `blocks()` relationship and `syncBlocks()` method
- [ ] Users can add, reorder (up/down), edit, and remove text blocks
- [ ] Users can add, edit, and remove Excalidraw blocks (inline embedded)
- [ ] Notes detail page keeps existing tags, links, and activity layout
- [ ] Tests cover block CRUD and note update validation
- [ ] `composer qa` passes

## Out of Scope (Future)

- File blocks
- Image blocks
- Drag-and-drop block reorder
- Slash command menu
- Markdown export of mixed block types
- Block reuse across notes
- Block-level version history
