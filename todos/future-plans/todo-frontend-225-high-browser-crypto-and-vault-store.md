---
id: 225
section: frontend
status: todo
severity: high
---

# Build Browser Crypto Module And Local Vault Client

Build the browser-side crypto implementation (libsodium-wrappers-sumo) matching the shared protocol fixture, plus the local indexedDB-backed vault store.

## Browser Crypto

- X25519 member keys, Argon2id recovery-phrase derivation, HKDF subkeys, XChaCha20-Poly1305 record envelopes, chunked file content AEAD.
- BIP39-compatible recovery phrase generate/validate/restore.
- Interop: node test fixtures under `e2ee/test-vectors.json` prove the same envelope decrypts in browser and Go runtimes.

## Local Store

- IndexedDB for ciphertext envelopes, sync cursor, local record projections, and persisted plaintext search index.
- Decrypted keys remain in memory only; no team key or recovery phrase in localStorage/sessionStorage.
- Remove the full-record `sessionStorage` write in `useListDetailOverlay`.

## Acceptance Criteria

- [ ] Browser crypto round-trips fixed vectors (encrypt+decrypt match fixture)
- [ ] Tampered ciphertext fails decryption deterministically
- [ ] IndexedDB persists ciphertext + index; keys stay in memory
- [ ] No-plaintext browser-storage regression test passes

## Notes

- Plaintext index entries are readable under this origin by design (startup-performance tradeoff).