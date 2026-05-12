---
id: 5
section: backend
status: done
severity: low
---

# Move AuthorizesTeamResource to Shared Namespace

`AuthorizesTeamResource` lives in `App\Http\Requests\Tasks` namespace but is used generically across all domain FormRequests (bookmarks, contacts, notes, etc.).

## Current Location

`app/Http/Requests/Tasks/AuthorizesTeamResource.php`

## Implementation Plan

### Approach

Move to `App\Http\Requests\Concerns\` namespace. Simple file move + namespace update.

### Current Location

`app/Http/Requests/Tasks/AuthorizesTeamResource.php`

### Files Using It (24 FormRequests)

- `app/Http/Requests/Tasks/` — SaveTaskRequest, DeleteTaskRequest, SaveTaskCommentRequest, DeleteTaskCommentRequest, SaveProjectRequest (5 files, all share the namespace so no import update needed)
- `app/Http/Requests/Calendar/` — SaveCalendarEventRequest, DeleteCalendarEventRequest
- `app/Http/Requests/Contacts/` — SaveContactRequest, DeleteContactRequest
- `app/Http/Requests/Bookmarks/` — SaveBookmarkRequest, DeleteBookmarkRequest
- `app/Http/Requests/Subscriptions/` — SaveSubscriptionRequest, DeleteSubscriptionRequest
- `app/Http/Requests/Notes/` — SaveNoteRequest, DeleteNoteRequest
- `app/Http/Requests/Collections/` — SaveCollectionRequest, DeleteCollectionRequest
- `app/Http/Requests/Log/` — SaveLogEntryRequest, DeleteLogEntryRequest
- `app/Http/Requests/RecordLinks/` — StoreRecordLinkRequest, RecordLinkCandidatesRequest
- `app/Http/Requests/RecordTags/` — StoreRecordTagRequest, DeleteRecordTagRequest, RecordTagCandidatesRequest

### Steps

1. Move file: `app/Http/Requests/Tasks/AuthorizesTeamResource.php` → `app/Http/Requests/Concerns/AuthorizesTeamResource.php`
2. Update namespace: `App\Http\Requests\Tasks` → `App\Http\Requests\Concerns`
3. Update all 19 cross-namespace `use` statements across non-Tasks FormRequest classes (5 Tasks FormRequests share the namespace, no import needed)

### Dependencies

- Should be done after todo-3 (auth consolidation) since that changes the trait's responsibilities

## Acceptance Criteria

- [x] Move to `App\Http\Requests\Concerns\AuthorizesTeamResource`
- [x] Update all imports across domain FormRequest classes
