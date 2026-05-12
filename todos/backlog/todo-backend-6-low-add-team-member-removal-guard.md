---
id: 6
section: backend
status: todo
severity: low
---

# Guard Against Last Member Self-Removal

`TeamMemberController::destroy()` prevents removing the team owner but allows any other member (including the last non-owner) to remove themselves, potentially leaving a team with only the owner or empty.

## File

`app/Http/Controllers/Teams/TeamMemberController.php` lines 38-57

## Implementation Plan

### Approach

Add a count check before deletion in `TeamMemberController::destroy()`.

### Current Code

`app/Http/Controllers/Teams/TeamMemberController.php:38-58`:
1. `Gate::authorize('removeMember', $team)` — policy check
2. Aborts 403 if target user is the owner (line 43)
3. Deletes membership (lines 45-47)
4. Switches current team if needed (lines 49-55)

### Steps

1. After the owner check (line 43), add membership count check:
   ```php
   if ($team->memberships()->count() <= 1) {
       abort(403, 'Cannot remove the last team member.');
   }
   ```
2. Consider: should we also prevent removing the last non-owner member (leaving only the owner)? If so, check `$team->memberships()->where('role', '!=', 'owner')->count() <= 1` — but this depends on the team roles implementation
3. Add test: attempting to remove the last member returns 403

## Acceptance Criteria

- [ ] Add check preventing self-removal when user is the last member (or last non-owner member)
- [ ] Return appropriate error message
