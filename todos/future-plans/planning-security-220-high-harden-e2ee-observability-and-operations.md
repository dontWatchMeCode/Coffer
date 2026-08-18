---
id: 220
section: security
status: planning
severity: high
---

# Harden E2EE Boundaries, Observability, And Team Operations

Remove existing plaintext sinks for E2EE teams and define operational limits honestly before enabling the feature.

## Plaintext Sinks In Current Application

- `activity_log` stores model old/new values and rich block payloads through model activity configuration and `app/Services/ActivityLogger.php`.
- Telescope request, model, query, and response watchers can persist record values in `config/telescope.php`.
- Sentry, HTTP/access logs, exceptions, queues, email, and browser logs can capture request payloads or search terms if not redacted.
- Current MCP services serialize plaintext in `app/Services/McpRecordPayload.php` and search it in `app/Services/McpRecordReadService.php`.
- Current page controllers send plaintext records, previews, excerpts, and aggregates through Inertia.
- The filesystem stores raw file bytes through `app/Services/McpFileContent.php` and feature file controllers.

## Required Changes

- Disable or replace plaintext activity logging for E2EE teams with metadata-only event records: actor ID, type, record ID, event, timestamp, and version.
- Configure Telescope, Sentry, logs, error reporting, queue payloads, mail, and browser diagnostics to redact encrypted-team request/response bodies, tokens, recovery material, and search terms.
- Disable direct server MCP semantic tools for E2EE teams; only ciphertext transport remains remote.
- Ensure encrypted team page responses contain no decrypted fields before Inertia history is written.
- Set a strict production CSP, keep DOMPurify/markdown/excalidraw/mermaid sanitization audited, and minimize third-party script origins.
- Add request-size limits, ciphertext-envelope validation, token throttling, encrypted file limits, and abuse protections without inspecting values.
- Document metadata leakage: membership, type, record existence, IDs, relationship IDs, timestamps, ciphertext sizes, change cadence, and task project IDs needed for scope enforcement.

## Threat Model Limits

- E2EE protects against database, backup, log, and passive server compromise where no client code is altered.
- It does not protect an unlocked browser/CLI, a malicious active team member, malware, browser extensions, or XSS.
- A compromised web server can serve malicious JavaScript and exfiltrate browser keys/plaintext. CSP and build integrity reduce but do not eliminate this risk.
- A removed member keeps any content they previously decrypted. New key epochs only protect future data until old ciphertext is fully re-encrypted.
- Server denial of service, replay, rollback, and deletion remain possible; authenticated versions and local sync checkpoints detect tampering where possible but cannot ensure availability.

## Team Operations

- Require recovery-phrase confirmation during setup and provide an encrypted export/restore workflow that never uploads the phrase.
- Document member invitation, key sharing, device addition, removal, rotation, password reset, loss recovery, and emergency team reset flows.
- Do not claim full removal revocation until full re-encryption has shipped and completed.
- Create production runbooks for locked members, failed rotation, ciphertext corruption, sync conflicts, token revocation, and E2EE team deletion.
- Require independent cryptographic/security review before marketing the feature as E2EE or zero knowledge.

## Explicit Non-Work

- No migration, backfill, validation, cleanup, or deletion of historical plaintext data is part of this roadmap.
- Do not describe teams with pre-existing plaintext data as fully E2EE solely because new writes are encrypted.

## Acceptance Criteria

- [ ] Automated tests and log inspections show no decrypted E2EE record value, search term, key, recovery phrase, or file byte reaches Laravel observability systems
- [ ] E2EE activity history contains metadata only
- [ ] Direct remote MCP semantic access is unavailable for E2EE teams
- [ ] Production CSP and sanitization controls are tested against known rendered-content attack paths
- [ ] Team lifecycle and recovery runbooks are tested with two members and multiple devices
- [ ] Security review documents accepted metadata leakage and threat-model limits before release
