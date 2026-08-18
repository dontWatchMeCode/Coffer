---
id: 230
section: security
status: todo
severity: high
---

# CSP Hardening, Vault Enforcement, Runbooks, And Security Review

Harden the production boundary and document operations honestly before enabling the feature.

## Required Changes

- Strict production CSP and audited sanitization of third-party-rendered content (DOMPurify/markdown/excalidraw/mermaid).
- Request-size limits, envelope validation, token throttling, encrypted-file limits, and abuse protections on vault routes without inspecting values.
- Recovery-phrase confirmation during setup; encrypted export/restore that never uploads the phrase.
- Document member invitation, key sharing, device addition, removal, rotation, password reset, loss recovery, emergency team reset flows.
- Production runbooks for locked members, failed rotation, ciphertext corruption, sync conflicts, token revocation, E2EE team deletion.
- Document metadata leakage and threat-model limits.
- Independent cryptographic/security review before release.

## Explicit Non-Work

- No migration, backfill, validation, cleanup, or deletion of historical plaintext data.
- No full-removal-revocation claims until full re-encryption has shipped and completed.

## Acceptance Criteria

- [ ] Production CSP and sanitization controls tested against known rendered-content attack paths
- [ ] Vault route enforcement/limits covered by tests
- [ ] Runbooks and threat-model limits document accepted metadata leakage before release
- [ ] Recovery/rotation flows tested with two members and multiple devices

## Notes

- Security review artifact belongs in `docs/e2ee/`.