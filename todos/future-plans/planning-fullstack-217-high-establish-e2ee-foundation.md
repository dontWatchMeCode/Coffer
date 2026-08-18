---
id: 217
section: fullstack
status: planning
severity: high
---

# Establish E2EE Protocol And Ciphertext Sync Foundation

Make Laravel an authenticated, authorized ciphertext transport for E2EE teams. The server must never receive a team key or domain-record plaintext.

## Product Decisions

- Encrypt all user-entered values, including titles, tags, statuses, dates, amounts, filenames, MIME types, and file contents.
- Leave only operational metadata readable: team/user/record/relationship IDs, record type, key epoch, version, timestamps, deletion state, token abilities, task `project_id` where required for token scope, and ciphertext sizes.
- Support one team with multiple members.
- Recovery uses a member-held recovery phrase. Laravel cannot recover keys or data.
- Do not migrate, backfill, or purge existing plaintext data in this work. E2EE applies only to newly enabled teams/data.

## Protocol

- Use a versioned, language-neutral binary/JSON envelope format shared by Vue and Go.
- Use `libsodium-wrappers-sumo` in the browser and compatible Go primitives.
- Use X25519 member encryption keys, Argon2id for recovery-phrase key derivation, HKDF-SHA-256 for subkeys, and XChaCha20-Poly1305 for record payloads.
- Generate a random 256-bit team data key for each key epoch.
- Wrap each team data key to every active member/device public key; store only the wrapped envelope on Laravel.
- Bind each record encryption to team ID, record type, record ID, key epoch, and optimistic version with AEAD additional authenticated data.
- Use a fresh random 192-bit nonce for every record encryption.
- Define fixed browser-to-Go and Go-to-browser test vectors before building feature code.

## Laravel Scope

- Add member public-key/device registration, team key epochs, member key envelopes, encrypted record envelopes, tombstones, optimistic versions, and monotonic sync cursors.
- For E2EE teams, keep `Mcp::web('/mcp/records', ...)` as the remote MCP endpoint but replace its semantic record tools with ciphertext-only `vault.pull`, `vault.push`, `vault.keys.get`, `vault.keys.put`, `vault.files.get`, and `vault.files.put` transport tools using `AuthenticateMcpToken`.
- Enforce existing resource abilities, team feature flags, and task-project token scope against plaintext operational metadata only.
- Reject stale writes with a conflict response; make retries idempotent.
- Keep the current team membership authorization boundary in `app/Http/Middleware/EnsureTeamMembership.php` and `app/Concerns/BelongsToTeam.php`.
- Add an explicit E2EE team feature flag; plaintext teams continue using the existing application behavior.

## Existing Paths To Replace For E2EE Teams

- Server global and list search in `app/Services/RecordSearchService.php` and `app/Concerns/Filterable.php`.
- MCP plaintext reads and writes in `app/Services/McpRecordReadService.php`, `app/Services/McpRecordWriteService.php`, and `app/Services/McpRecordPayload.php`.
- Server-side preview generation in `app/Services/RecordLinkHelper.php`.
- Server-side record validation in `app/Validation/DomainRecordValidation.php`, except encrypted-envelope shape, size, relation, and authorization validation.

## Team Key Lifecycle

- A new invitee registers a public key before receiving data access.
- An already unlocked member wraps the current team key for the invitee after acceptance.
- Removing a member creates a new key epoch and removes their envelopes.
- A remaining unlocked member must generate and distribute the new team key.
- Full re-encryption is required to prevent a removed member decrypting previously synced ciphertext. This may be a later delivery phase but must not be represented as completed revocation before it exists.
- A password reset does not recover E2EE material; recovery phrase or a trusted, unlocked current member is required.

## Out Of Scope

- Migration, backfill, validation, or deletion of old plaintext records and backups.
- Server-readable analytics, previews, full-text search, or AI access.
- CRDT collaboration and automatic concurrent rich-text merges.
- Native/mobile clients.

## Acceptance Criteria

- [ ] Laravel persists and returns only ciphertext plus the defined operational metadata for an E2EE team
- [ ] Laravel never accepts or emits a team data key, decrypted record value, search term, filename, tag, or plaintext file bytes
- [ ] Member/device key envelopes can be registered and fetched only by authorized active team members
- [ ] Pull, push, delete, retry, and stale-write conflict behavior is cursor- and version-tested
- [ ] Browser and Go compatibility tests prove the same envelope decrypts in both runtimes
- [ ] Existing non-E2EE team behavior remains covered by regression tests
