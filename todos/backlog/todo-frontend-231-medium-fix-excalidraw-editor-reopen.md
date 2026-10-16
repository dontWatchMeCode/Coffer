---
id: 231
section: frontend
status: todo
severity: medium
---

# Fix Intermittent Excalidraw Editor Loading on Reopen

The Excalidraw edit view sometimes fails to load after an edit → save → edit cycle, for example in notes.

## Reproduction

1. Open a note containing an Excalidraw drawing.
2. Edit the drawing and save.
3. Edit the drawing again; the editor sometimes fails to load.

## Acceptance Criteria

- [ ] Reproduce and identify the cause of the intermittent loading failure.
- [ ] Excalidraw reliably loads the saved drawing after repeated edit → save → edit cycles.
- [ ] Check other views using the shared Excalidraw editor for the same issue.
- [ ] Add regression coverage for reopening the editor after saving.
