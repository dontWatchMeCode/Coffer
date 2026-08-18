---
id: 222
section: fullstack
status: todo
severity: high
---

# Add Vault Schema, Models, And E2EE Team Feature Flag

Add migrations and Eloquent models for ciphertext-only team vault state, plus the `e2ee` team feature flag.

## Schema

- `e2ee_member_keys`: per-member device public keys (team, user, device label, x25519 public key, active, last seen).
- `e2ee_key_epochs`: per-team key epochs (epoch integer, status, created_by).
- `e2ee_key_envelopes`: wrapped team keys per (epoch, member key) — opaque JSON.
- `e2ee_record_envelopes`: latest envelope state per (team, type, id) with optimistic version, tombstone flag, and task-project id for token scope.
- `e2ee_sync_log`: append-only monotonic change log; `id` column is the sync cursor.
- `e2ee_files`: encrypted file blob metadata + storage path of ciphertext bytes.
- Add `E2ee` case to `App\Enums\TeamFeature`.

## Acceptance Criteria

- [ ] Migrations are reversible and follow existing SQLite-friendly conventions (explicit FK indexes)
- [ ] Models follow existing traits/type conventions (BelongsToTeam not used — vault is team-scoped explicitly)
- [ ] `Team::hasFeature(TeamFeature::E2ee)` gates E2EE path
- [ ] Feature toggle test covers E2EE flag on/off

## Notes

- No plaintext columns for values, search terms, filenames, MIME types, or content — only operational metadata.
- Existing plaintext teams keep current behavior.