---
id: 215
section: backend
status: todo
severity: medium
---

# Add Files MCP Integration

Expose Files records through the existing MCP record tooling so agents can discover, read, tag, and link private file metadata without exposing private file bytes publicly.

## Context

- Files are implemented as private, team-scoped `FileItem` records.
- Web search/link/tag flows already support `file` through `RecordSearchRegistry` and `RecordLinkHelper`.
- MCP record tools do not yet support Files because `file` is not registered in MCP record type lists or validators.

## Scope

1. Add `file` to MCP record type registration and token abilities.
2. Add Files metadata fields to MCP schema/payload output.
3. Support MCP read/search/get operations for Files metadata.
4. Support MCP tag and record-link operations for Files.
5. Decide and implement write behavior for Files: metadata-only updates are acceptable; byte upload through MCP should stay out of scope unless explicitly designed.
6. Ensure MCP responses use private inline/download route URLs only when appropriate and never expose public storage URLs.

## Acceptance Criteria

- [ ] MCP schema lists `file` as a supported record type when the team Files feature is enabled.
- [ ] MCP token abilities can grant/deny Files access independently.
- [ ] MCP search/get returns Files metadata including title, description, original name, MIME type, size, and private route URLs if included.
- [ ] MCP can tag Files records and link Files to other records.
- [ ] Disabled Files team feature removes Files from MCP search/schema/link candidates.
- [ ] MCP cannot expose raw file bytes or public storage URLs.
- [ ] Targeted Pest tests pass.
