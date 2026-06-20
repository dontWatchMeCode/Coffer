---
id: 210
section: backend
status: todo
severity: medium
---

# Fix Guzzle Audit Findings

Resolve the remaining Composer audit findings that were intentionally deferred because the fixed package versions are newer than the two-week package age policy.

## Context

- `guzzlehttp/guzzle` remains flagged for `CVE-2026-55767` and `CVE-2026-55568`; fixed versions require `7.12.1` or newer.
- `guzzlehttp/psr7` remains flagged for `CVE-2026-55766`, `CVE-2026-49214`, and `CVE-2026-48998`; fixed versions require `2.12.1` or newer.
- A pinned `guzzlehttp/psr7:2.10.2` update was checked, but Composer still blocks it because it remains affected by the newer June advisory.

## Recommended Work

1. Re-run `composer audit` after `guzzlehttp/guzzle:7.12.1` and `guzzlehttp/psr7:2.12.1` are at least 14 days old.
2. Apply the smallest non-breaking Composer update that resolves both package advisories.
3. Re-run backend QA and Composer audit.

## Acceptance Criteria

- [ ] `guzzlehttp/guzzle` audit findings are resolved without bypassing the two-week package age policy
- [ ] `guzzlehttp/psr7` audit findings are resolved without bypassing the two-week package age policy
- [ ] `composer qa` passes
- [ ] `composer audit` has no remaining unreviewed backend runtime findings
