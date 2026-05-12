---
id: 4
section: backend
status: todo
severity: low
---

# Extract Shared resolveModel Method

`RecordLinkController` (line 25-35) and `RecordTagController` (line 113-124) have near-identical `resolveModel()` methods. The only difference: `RecordTagController` adds an `instanceof LinkableRecord` type guard.

## Acceptance Criteria

- [ ] Extract to shared trait or service (e.g. `ResolvesLinkableRecord`)
- [ ] Include the `LinkableRecord` type guard in the shared implementation
- [ ] Both controllers use shared implementation
