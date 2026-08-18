---
id: 221
section: fullstack
status: todo
severity: high
---

# Define Shared Vault Envelope Protocol And Interop Test Vectors

Define the versioned, language-neutral envelope format shared by the Vue browser client and the Go CLI before implementing feature code. The server treats every envelope as opaque ciphertext.

## Protocol Definition

- Versioned JSON envelope with base64url payload fields: `v`, `kid`, `rid` (record type), `id` (record id), `ver` (optimistic version), `alg`, `n` (24-byte nonce), `ct` (ciphertext+tag).
- Record AAD constructed deterministically: `e2ee:v1:{teamId}:{rid}:{id}:{kid}:{ver}`.
- X25519 member encryption keys derived from a recovery phrase via Argon2id + HKDF-SHA-256.
- Key wrapping: ephemeral X25519 + HKDF-SHA-256 wrap key + XChaCha20-Poly1305.
- Record payloads: XChaCha20-Poly1305 with a fresh random 192-bit nonce per encryption.
- Files: metadata envelope + chunked XChaCha20-Poly1305 content store; never plaintext to server.
- BIP39-compatible mnemonic wordlist for recovery phrases, shared by both runtimes.
- A canonical fixture of fixed test vectors under `e2ee/` that both runtimes assert against.

## Acceptance Criteria

- [x] Protocol spec is documented in `docs/e2ee/protocol.md`
- [x] A fixed vector fixture exists in `e2ee/test-vectors.json`
- [x] The Go reference crypto implementation can produce/consume the fixture
- [ ] The browser libsodium implementation can produce/consume the same fixture (completes in todo-frontend-225)

## Notes

- Laravel never receives a team key, decrypted value, search term, filename, or plaintext file byte.
- Doctrine: fixed vectors are generated from the reference implementation and frozen.
- Implemented in `cli/crypto/` with vector tests passing; browser interop lands with todo-frontend-225.