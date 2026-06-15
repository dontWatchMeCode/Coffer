---
id: 207
section: backend
status: planning
severity: high
---

# Realtime Content Sync

Implement sync for collaborative content editing so users see updates made by other users without refreshing.

## Goal

- Save edits through normal server-side validation and authorization
- Broadcast successful updates to other users viewing the same content
- Prevent stale edits from silently overwriting newer changes
- Keep the first implementation simple before considering full collaborative editing

## Recommended Approach

1. Use regular `PATCH` requests for saves from the Inertia/Vue editor.
2. Add optimistic concurrency using `updated_at` or a dedicated `version` column.
3. Broadcast a `ContentUpdated` event after successful saves.
4. Subscribe viewers to private channels scoped by team and content id.
5. Update other users' screens when an event is received.
6. Show a conflict message when a save is based on stale content.

## Scope

- Laravel broadcast event implementing `ShouldBroadcast`
- Private broadcast channel authorization
- Vue listener using Laravel Echo
- Conflict detection on update requests
- UI state for remote updates and stale-save conflicts

## Out Of Scope Initially

- Per-keystroke collaboration
- Presence cursors
- Rich-text operational transforms
- CRDT-based merge logic
- Automatic content merging

## Future Enhancements

- Add edit locks for high-risk content types
- Add presence indicators for users viewing or editing the same content
- Add debounced autosave after manual save behavior is stable
- Consider Yjs or another CRDT library if Google Docs-style editing becomes required
- Store revision history for rollback and conflict review

## Open Questions

- Which content types need realtime sync first?
- Should remote updates replace local content immediately or show a reload/update prompt?
- Should editing be lock-based or conflict-based for the first version?
- Is Laravel Reverb the preferred websocket backend for production?

## Acceptance Criteria

- [ ] Saving content broadcasts an update event to other viewers
- [ ] Other users see remote updates without a page refresh
- [ ] Users cannot overwrite newer content without seeing a conflict
- [ ] Broadcast channels are private and team-scoped
- [ ] Realtime behavior is covered by feature and frontend tests where practical
