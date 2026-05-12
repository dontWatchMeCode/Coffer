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

## Acceptance Criteria

- [ ] Add check preventing self-removal when user is the last member (or last non-owner member)
- [ ] Return appropriate error message
