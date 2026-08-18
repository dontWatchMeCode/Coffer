---
id: 223
section: fullstack
status: todo
severity: high
---

# Build Vault Ciphertext Transport Services And MCP Tools

Replace the semantic record tools with opaque `vault.*` transport for E2EE teams while keeping `/mcp/records` as the remote endpoint.

## Services

- `VaultKeyService`: member key registration, epoch creation, team-key wrapping for members, epoch rotation on member removal.
- `VaultRecordService`: `pull` (changes after cursor, permission + task-project filtered), `push` (upsert/tombstone with optimistic version conflict detection, idempotent retries).
- `VaultFileService`: `put` (encrypted blob storage) and `get` (fetch blob + metadata).

## MCP Tools

- `vault.keys.get`, `vault.keys.put`, `vault.pull`, `vault.push`, `vault.files.get`, `vault.files.put` on the existing `Mcp::web('/mcp/records', ...)` endpoint.
- Semantic `records.*` tools are not registered for E2EE teams; vault tools are not registered for plaintext teams.
- Same tools exposed as web routes for the browser client (`POST /{current_team}/vault/*`).

## Checks

- Enforce token abilities, team membership, feature flags, and task-project token scope against plaintext operational metadata only.
- Stale write returns a conflict response; retries are idempotent.

## Acceptance Criteria

- [ ] `tools/list` advertises `vault.*` for E2EE teams and `records.*` for plaintext teams
- [ ] Pull/push cursor and version behavior is tested
- [ ] Task-project and ability scoping is enforced on vault payload metadata
- [ ] Retries and stale-write conflicts are tested
- [ ] Encrypted file put/get works without server plaintext inspection

## Notes

- `AuthenticateMcpToken` remains the auth boundary; plaintext validation services are not reached for E2EE teams.