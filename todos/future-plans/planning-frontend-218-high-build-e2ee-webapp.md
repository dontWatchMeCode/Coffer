---
id: 218
section: frontend
status: planning
severity: high
---

# Build Browser E2EE Experience And Local Search

Turn the Vue/Inertia application into an E2EE client for enabled teams: unlock keys locally, decrypt ciphertext locally, and perform all value-based behavior in the browser.

## Product Decisions

- IndexedDB persists a plaintext local search index to avoid rebuilding it on every unlock.
- IndexedDB ciphertext remains the canonical browser cache; decrypted keys remain in memory only.
- Browser search, tags, sorting, previews, links, filters, dashboards, and insights run locally after unlock.
- Existing plaintext data is out of scope. Do not build conversion or compatibility paths.

## Key And Sync UX

- Add E2EE setup, unlock, lock, recovery-phrase confirmation, member-key registration, and invitee-waiting states.
- Prompt an existing unlocked member to authorize a newly accepted member by wrapping the team key to their public key.
- On team switch, unlock the relevant team key, pull encrypted changes, decrypt them, and update the local cache/index.
- Lock on explicit user action and clear decrypted in-memory state on logout, team removal, and browser lifecycle events where practical.
- Surface unavailable-key, tampered-ciphertext, stale-write, sync failure, rotation-required, and conflict states without falling back to plaintext server requests.

## Local Storage And Search

- Use IndexedDB for encrypted envelopes, sync cursor, local record projections, and the persisted local index.
- Use a small client-side index/search library or a documented bounded scan; preserve existing type-prefix behavior from `app/Services/RecordTypeRegistry.php`.
- Do not send search queries to `team.search` or feature index routes; replace `resources/js/composables/useTeamSearch.ts` and `resources/js/composables/useSearch.ts` for E2EE teams.
- Store no team key, recovery phrase, or decrypted full record in localStorage or sessionStorage.
- Remove the full-record `sessionStorage` write in `resources/js/composables/useListDetailOverlay.ts`.

## Record And File UX

- Encrypt each record payload before create/update; decrypt only after authorized ciphertext fetches.
- Replace server-provided titles, excerpts, previews, tag candidates, link candidates, and list sorting with local projections.
- Move dashboard and insight calculations currently performed by `app/Http/Controllers/DashboardController.php` and feature insight controllers into browser state.
- Encrypt file bytes in the browser before upload; download ciphertext and decrypt client-side before display/save.
- Derive image previews and file metadata in the browser; Laravel must not inspect decrypted bytes.
- Use last-writer-wins with visible version conflicts for the first release. Do not add CRDTs initially.

## Server Data Exposure To Remove For E2EE Teams

- Plaintext Inertia page props from feature page controllers.
- `RecordSearchService`, `Filterable`, tag candidates, and link candidates.
- Server activity history value diffs and plaintext activity attributes.
- Server dashboards, subscription/task/calendar insight aggregation, and server-generated previews.
- Server file MIME inspection, dimensions, inline previews, and decrypted downloads.

## Security Notes

- Persisted plaintext index entries are readable to code running under this origin; this is an accepted startup-performance tradeoff.
- XSS compromises the unlocked browser and can expose decrypted records/index values. Tight CSP, dependency control, and DOM sanitization are mandatory.
- Inertia history encryption can reduce browser-history exposure but is not E2EE and must not be treated as a substitute.

## Acceptance Criteria

- [ ] An enabled team can create, read, update, delete, and search encrypted records without Laravel receiving record values
- [ ] Browser search supports record-type prefixes and local ranking without requests containing search terms
- [ ] Tags, links, previews, sorting, filters, dashboards, and insights work from decrypted local projections
- [ ] Encrypted files upload, download, and render without server-side plaintext inspection
- [ ] Logout and explicit lock clear in-memory keys and decrypted reactive state
- [ ] Frontend tests cover unlock, ciphertext round-trips, local search, tamper failures, sync conflicts, and no-plaintext browser-storage regressions
