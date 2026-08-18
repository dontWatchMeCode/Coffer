---
id: 229
section: security
status: todo
severity: high
---

# Replace Activity History And Plaintext Observability For E2EE Teams

Replace plaintext activity logging with metadata-only events and redact E2EE request/response bodies across observability systems.

## Required Changes

- `ActivityLogger` and model activity config log metadata-only events for E2EE teams: actor, type, record id, event, timestamp, version. Never old/new values or rich block payloads.
- Telescope request/model/query watchers redact encrypted-team bodies.
- Sentry, HTTP/access logs, exceptions, queue payloads, mail, protocol, and browser diagnostics redact encrypted-team request/response bodies, tokens, recovery material, and search terms.
- Ensure encrypted team page responses contain no decrypted fields before Inertia history is written.

## Acceptance Criteria

- [ ] Automated tests and log inspections show no decrypted E2EE value/search/key/phrase/file byte reaches Laravel observability
- [ ] E2EE activity history contains metadata only
- [ ] Redaction config is tested for request bodies and exception logging

## Notes

- Independent cryptographic/security review required before marketing as E2EE / zero knowledge (see todo 230).