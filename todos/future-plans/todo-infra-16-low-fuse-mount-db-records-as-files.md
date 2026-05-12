---
id: 16
section: infra
status: todo
severity: low
---

# FUSE Mount: Map DB Records to Local Files

Build a Go CLI tool that uses FUSE to mount a local directory, mapping database records (tasks, projects, etc.) as files and folders.

## Motivation

Browse and edit dashboard records (tasks, contacts, notes, bookmarks, collections, log entries, calendar events) from the terminal using standard file tools (ls, cat, vim, grep, etc.).

## Approach

Go CLI using `go-fuse` (or `bazil/fuse`) library. Reads from the app's MySQL/Postgres database. Writes translate back to Eloquent-compatible DB updates.

## Proposed Directory Structure

```
/mnt/dashboard/
├── projects/
│   ├── My Project/
│   │   ├── task-1-do-the-thing.md
│   │   └── task-2-something-else.md
├── contacts/
│   └── jane-doe.md
├── notes/
│   └── meeting-notes.md
├── bookmarks/
│   └── useful-link.md
├── collections/
│   └── my-collection.md
├── calendar/
│   └── 2026-05-12-team-meeting.md
└── log/
    └── 2026-05-12-quick-note.md
```

## Implementation Plan

1. **DB layer** — Go structs mapping to the app's tables (projects, tasks, contacts, notes, bookmarks, collections, calendar_events, log_entries, tags, links)
2. **FUSE filesystem** — implement `readdir`, `read`, `write`, `create`, `mkdir`, `unlink`, `rmdir` ops
3. **File format** — each record serialized as Markdown with YAML frontmatter (title, status, assignee, dates, etc.) and body as content
4. **Write-back** — parse Markdown frontmatter on write, translate field changes to SQL UPDATEs
5. **CLI** — `dashboard-fuse mount /mnt/dashboard --db-url=...` with flags for read-only mode, team filtering
6. **Config** — read DB credentials from the app's `.env` or accept connection string

## Open Questions

- Should writes be real-time or batched?
- Handle concurrent access (multiple terminals editing same record)?
- Conflict resolution strategy?

## Acceptance Criteria

- [ ] Mount exposes all record types as a directory tree
- [ ] Read-only browsing works with standard file tools
- [ ] Editing a file writes changes back to the database
- [ ] Creating/deleting files creates/deletes records
- [ ] CLI accepts DB connection config
- [ ] Read-only mode flag supported
