---
id: 226
section: frontend
status: todo
severity: high
---

# Build Browser E2EE Setup, Unlock, Sync, And Local Search UX

Turn the Vue/Inertia app into an E2EE client for enabled teams: unlock locally, pull/decrypt ciphertext, update the local index, and run search/dashboard/insight behavior in the browser.

## Key And Sync UX

- E2EE setup, unlock, lock, recovery-phrase confirmation, member-key registration, invitee-waiting states.
- Prompt an unlocked member to authorize a new member by wrapping the team key.
- On team switch, unlock the relevant team key, pull encrypted changes, decrypt, update local cache/index.
- Lock clears decrypted in-memory state on explicit action, logout, team removal, and browser lifecycle events.

## Local Search

- Client-side search over the persisted local index with type-prefix and `#tag` syntax.
- No search queries sent to `team.search` or feature index routes for E2EE teams.
- Replace `useTeamSearch`/`useSearch` behavior for E2EE teams; surface unavailable-key, tampered-ciphertext, stale-write, sync failure, rotation-required, and conflict states.

## Acceptance Criteria

- [ ] Enabled team can create/read/update/delete/search records without Laravel receiving values
- [ ] Unlock, lock, and team-switch flows work against the local store
- [ ] Sync conflicts surface visibly; no fallback to plaintext server requests
- [ ] Frontend tests cover unlock, sync, conflict, and lock-clearing state

## Notes

- No CRDTs; last-writer-wins with visible conflicts for the first release.