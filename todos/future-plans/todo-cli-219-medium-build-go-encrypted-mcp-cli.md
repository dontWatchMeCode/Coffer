---
id: 219
section: cli
status: todo
severity: medium
---

# Build Go CLI And Local Decrypted MCP Server

Build a Filen-style Go CLI that authenticates against Laravel's ciphertext endpoint, decrypts locally, maintains a local search index, and exposes semantic MCP tools over local stdio.

## Architecture

```text
MCP host -> dashboard mcp (local stdio) -> local decrypted SQLite/FTS5 cache
                                           |
                                           +-> Laravel /mcp/records ciphertext transport
```

- MCP hosts must connect to the local CLI, not directly to Laravel, to receive useful decrypted tool results.
- For E2EE teams, Laravel `/mcp/records` exposes only opaque `vault.pull`, `vault.push`, key-envelope, and encrypted-file transport tools. It does not expose semantic `records.*` tools remotely.
- The CLI is trusted by the user. Decrypted values returned by a local MCP tool may be sent to the configured AI provider; this is an explicit approved disclosure boundary.

## Technology

- Go with the official `github.com/modelcontextprotocol/go-sdk`.
- SQLite with FTS5 for local decrypted projections and search.
- OS keychain for the Laravel MCP token; require explicit opt-in fallback only where no keychain exists.
- Compatible Go implementation of the browser's versioned encryption envelope. Do not create a Go-only crypto format.
- Distribute signed macOS, Linux, and Windows binaries with reproducible release checks where feasible.

## Commands

- `dashboard login`: register the Laravel endpoint and MCP token.
- `dashboard pair`: unlock with recovery phrase or pair with an unlocked existing client; register device key and retrieve a team-key envelope.
- `dashboard sync`: pull ciphertext cursor changes, decrypt locally, apply tombstones, process encrypted outbox writes, and report conflicts.
- `dashboard search`: query local FTS5 index.
- `dashboard mcp`: expose local stdio MCP tools.
- `dashboard lock` and `dashboard logout`: remove in-memory key state and local credentials/cache according to documented user choice.

## MCP Tool Surface

- Preserve semantic tool names where practical: `records.search`, `records.get`, `records.create`, `records.update`, `records.delete`, link, tag, and comment operations.
- Run search, schema validation, related-record resolution, tag resolution, and payload rendering locally.
- Queue offline writes in an encrypted/local outbox; push idempotently when connectivity returns.
- Use local authorization projection as UX only. Laravel remains authoritative for token abilities, team membership, resource type, and allowed task project metadata at sync time.

## File Support

- Encrypt files locally before upload and decrypt them after download.
- Use chunked authenticated encryption for large files.
- Never send decrypted file bytes or filename/MIME values to Laravel.

## Security And Operations

- Never accept passphrases or recovery phrases as command arguments or environment variables.
- Write MCP protocol only to stdout; write diagnostics to stderr.
- Do not log decrypted payloads, search terms, keys, tokens, or recovery material.
- Lock key material after process termination; zero memory where the Go runtime and library APIs permit.
- Test offline operation, retry/idempotency, corruption, wrong key, stale write, removed-member, and server rollback behavior.

## Out Of Scope

- Supporting the current direct Laravel MCP tools for E2EE teams.
- Existing plaintext-data migration.
- A WebDAV/FUSE mount; the existing `todo-infra-16-low-fuse-mount-db-records-as-files.md` direct-database approach is incompatible with E2EE and would need a separate local-CLI-backed design.

## Acceptance Criteria

- [ ] CLI binaries authenticate with the existing token model without exposing keys to Laravel
- [ ] CLI synchronizes only ciphertext with Laravel and serves decrypted data only through local commands/MCP
- [ ] Local MCP tools provide search and CRUD from the local decrypted cache
- [ ] Browser and CLI can read each other's encrypted record and file envelopes
- [ ] Offline writes sync safely with idempotent retries and visible conflicts
- [ ] CLI tests prove no secrets or plaintext appear in stdout, logs, config, or remote requests
