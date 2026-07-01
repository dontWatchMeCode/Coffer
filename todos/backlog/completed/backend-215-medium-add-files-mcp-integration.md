---
id: 215
section: backend
status: done
severity: medium
---

# Add Files MCP Integration

Expose Files records through the existing MCP record tooling so agents can discover, read, tag, link, and upload private file metadata without exposing private file bytes publicly.

## Context

- Files are implemented as private, team-scoped `FileItem` records.
- Web search/link/tag flows already support `file` through `RecordSearchRegistry` and `RecordLinkHelper`.
- MCP record tools did not support Files because `file` was not registered in MCP record type lists or validators.

## Scope

1. Add `file` to MCP record type registration and token abilities.
2. Add Files metadata fields to MCP schema/payload output.
3. Support MCP read/search/get operations for Files metadata.
4. Support MCP tag and record-link operations for Files.
5. Support MCP create/update of Files with optional base64 byte upload; metadata-only creates remain supported.
6. Ensure MCP responses use private inline/download route URLs only when appropriate and never expose public storage URLs.

## Implementation Summary

### Files Modified

| File | Change |
|------|--------|
| `app/Services/McpRecordResolver.php` | Added `'file'` to `RECORD_TYPES` |
| `app/Models/McpToken.php` | Added `'file' => 'files'` to `RECORD_TYPES` |
| `app/Services/McpRecordValidator.php` | Added `file` cases to `fieldsFor`, `requiredFieldsFor`, `fieldNotesFor`, `rulesFor`, `messagesFor`, and `applyConditionalRules` (base64 validation) |
| `app/Services/McpRecordService.php` | Wired file content storage in `create()` and `update()` |
| `app/Services/McpRecordPayload.php` | Excluded `content` from file payload output |
| `app/Services/McpFileContent.php` | New service — decodes base64, detects MIME/dimensions, stores bytes |
| `app/Mcp/Servers/RecordsServer.php` | Updated instructions to mention files |
| `app/Mcp/Tools/CreateRecordTool.php` | Updated description to mention base64 content |
| `app/Models/FileItem.php` | Added null guard in `booted()` force-delete handler |
| `database/migrations/..._make_file_items_disk_and_path_nullable.php` | Made `disk`/`path` nullable |
| `app/Http/Controllers/Files/FileController.php` | Added null guards for nullable `disk`/`path` |
| `tests/Feature/McpRecordsServerTest.php` | Added 14 new tests covering file CRUD, base64 upload, validation, permissions, feature gating, payload privacy |

## Acceptance Criteria

- [x] MCP schema lists `file` as a supported record type when the team Files feature is enabled.
- [x] MCP token abilities can grant/deny Files access independently.
- [x] MCP search/get returns Files metadata including title, description, original name, MIME type, size, and private route URLs if included.
- [x] MCP can tag Files records and link Files to other records.
- [x] Disabled Files team feature removes Files from MCP search/schema/link candidates.
- [x] MCP cannot expose raw file bytes or public storage URLs.
- [x] MCP can create File records with optional base64 byte upload (metadata-only creates still work).
- [x] MCP can update File records, replacing bytes when content is provided.
- [x] Invalid base64, oversized content, and unsupported MIME types are rejected.
- [x] Target Pest tests pass (42 tests, 182 assertions).
