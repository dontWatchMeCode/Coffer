---
id: 224
section: fullstack
status: todo
severity: high
---

# Implement Team Key Lifecycle And Registration Flows

Implement the E2EE team key lifecycle: invitee registration, team-key sharing, epoch rotation on member removal, and recovery boundaries.

## Lifecycle Rules

- Invitee registers a public key before receiving data access.
- An unlocked existing member wraps the current team key for the invitee after acceptance.
- Removing a member creates a new key epoch and removes their envelopes (without claiming completed revocation until full re-encryption ships).
- Password reset does not recover E2EE material; recovery phrase or a trusted unlocked member is required.
- Server never accepts or emits a team data key or decrypted value.

## Out Of Scope

- Full re-encryption of previously synced ciphertext, historical plaintext migration, backfill, cleanup, or compatibility paths.

## Acceptance Criteria

- [ ] Member public key registration is only possible for authorized active team members
- [ ] Current-team-key envelope fetch is limited to active member devices
- [ ] Epoch rotation on member removal removes that member's envelopes and records a new epoch in the sync log
- [ ] Recovery-phrase-only flows never expose keys to Laravel
- [ ] Rotated/removed-member boundaries are tested with multiple members and devices

## Notes

- No revocation claims without documented re-encryption; see todo-security-220.