---
id: 228
section: cli
status: todo
severity: medium
---

# Build Go CLI Core, Local FTS5 Index, And Encrypted Sync

Build the Filen-style Go CLI that authenticates against Laravel's ciphertext endpoint, decrypts locally, maintains a local search index, and stays interoperable with the browser.

## Commands

- `dashboard login`: register the Laravel endpoint and MCP token (OS keychain, opt-in fallback elsewhere).
- `dashboard pair`: unlock with recovery phrase or pair with an unlocked client; register device key, retrieve team-key envelope.
- `dashboard sync`: pull ciphertext cursor changes, decrypt locally, apply tombstones, process encrypted outbox writes, report conflicts.
- `dashboard search`: query local FTS5 index.
- `dashboard mcp`: local stdio MCP server exposing semantic `records.*` tools.
- `dashboard lock` / `dashboard logout`: remove in-memory key state and local credentials/cache.

## Tech

- Go with `modelcontextprotocol/go-sdk`, SQLite+FTS5 for local projections, OS keychain for the Laravel MCP token.
- Compatible Go implementation of the shared vault envelope (no Go-only format).

## Acceptance Criteria

- [ ] CLI binaries authenticate with the existing token model without exposing keys to Laravel
- [ ] CLI synchronizes only ciphertext with Laravel and serves decrypted data only through local commands/MCP
- [ ] Local MCP tools provide search and CRUD from local decrypted cache
- [ ] Browser and CLI read each other's envelopes via the shared fixture
- [ ] Offline writes sync safely with idempotent retries and visible conflicts
- [ ] CLI tests prove no secrets/plaintext in stdout, logs, config, or remote requests

## Notes

- Decrypted values returned by local MCP are an explicit approved disclosure boundary to the AI provider.