---
id: 5
section: backend
status: todo
severity: low
---

# Move AuthorizesTeamResource to Shared Namespace

`AuthorizesTeamResource` lives in `App\Http\Requests\Tasks` namespace but is used generically across all domain FormRequests (bookmarks, contacts, notes, etc.).

## Current Location

`app/Http/Requests/Tasks/AuthorizesTeamResource.php`

## Acceptance Criteria

- [ ] Move to `App\Http\Requests\Concerns\AuthorizesTeamResource` or `App\Http\Requests\AuthorizesTeamResource`
- [ ] Update all imports across domain FormRequest classes
