---
id: 213
section: backend
status: todo
severity: high
---

# Investigate Intermittent 404 After Login

Diagnose and fix cases where a user sometimes lands on a 404 page immediately after logging in.

## Context

- The issue appears intermittent, so capture the exact redirect target and session/team state when it happens.
- Likely areas to check include Fortify login responses, intended URL handling, team redirect defaults, and route model binding for team-scoped routes.

## Recommended Work

1. Reproduce login from common entry points: direct `/login`, expired session, protected page redirect, and team-prefixed URLs.
2. Log or inspect the post-login redirect destination when the 404 occurs.
3. Verify the resolved current team and any URL defaults used after authentication.
4. Add a regression test for the failing login redirect path.
5. Apply the smallest fix that prevents redirecting to missing or unauthorized routes.

## Acceptance Criteria

- [ ] Intermittent post-login 404 is reproducible or root-caused from logs/Telescope
- [ ] Login redirects only target valid routes for the authenticated user
- [ ] Regression test covers the failing redirect scenario
- [ ] `php artisan test --compact` passes for the affected test file
