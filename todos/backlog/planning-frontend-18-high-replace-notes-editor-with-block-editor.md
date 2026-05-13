---
id: 18
section: frontend
status: planning
severity: high
---

# Plan Block Style Notes Editor

Replace the current note-level TipTap/Excalidraw format switch with a block style editor for Notes.

## Goal

Notes should support multiple blocks in a single note:

- TipTap rich text blocks
- Excalidraw drawing blocks
- File blocks
- Image blocks

The first implementation should be scoped to Notes only.

## Current Implementation Notes

- `resources/js/pages/notes/Show.vue` currently switches the whole note between `format: text` and `format: excalidraw`.
- Text editing uses `RichTextEditor`, which stores Markdown from TipTap.
- Drawings use `ExcalidrawEditor` and the `notes.drawing_data` JSON-style field.
- The `notes` table has `body`, `format`, and `drawing_data`; it does not yet have a multi-block document structure.

## Planning Work

1. Decide the persisted block format before implementation, likely a JSON column on `notes` that stores ordered blocks with type-specific payloads.
2. Define migration/backfill behavior from existing `body` and `drawing_data` into one or more blocks.
3. Decide whether file/image blocks use existing Laravel storage, direct uploads, or a new attachment model.
4. Decide how Markdown export should represent Excalidraw, files, and images.
5. Define keyboard and insertion UX similar to Notion without replacing existing page/sidebar layout conventions.

## Acceptance Criteria

- [ ] Document the block payload shape and migration path
- [ ] Existing text notes migrate or render as text blocks
- [ ] Existing Excalidraw notes migrate or render as drawing blocks
- [ ] Users can add, reorder, edit, and remove text blocks
- [ ] Users can add, edit, and remove Excalidraw blocks
- [ ] Users can add and remove file blocks
- [ ] Users can add and remove image blocks
- [ ] Notes detail page keeps existing tags, links, and activity layout
- [ ] Tests cover migration/backfill and note update validation
