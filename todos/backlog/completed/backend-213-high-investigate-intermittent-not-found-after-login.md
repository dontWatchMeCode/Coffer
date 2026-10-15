---
id: 213
section: backend
status: done
severity: high
---

# Investigate Intermittent 404 After Login

Diagnose and fix cases where a user sometimes lands on a 404 page immediately after logging in.

## Closure Note

Closed at user request after investigation; no fix applied and root cause remains unconfirmed.

Most likely explanation: a stale intended URL remains in the session. After successful login, both normal and two-factor login responses redirect to that destination without checking whether it still exists. A deleted record, renamed/deleted team, or changed membership could make the destination return 404/403. This would explain why direct login works while returning through an old destination sometimes fails.

Both dashboard routes exist. Existing authentication tests passed (8 tests, 23 assertions), but do not cover stale intended destinations. Telescope investigation was blocked by SQLite reporting “database disk image is malformed.” If this recurs, capture the exact post-login destination and add a regression test for that path.

The acceptance criteria below remain unchecked because the issue was closed without reproducing or fixing it.

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
