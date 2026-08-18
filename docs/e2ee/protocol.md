# E2EE Vault Protocol (v1)

Language-neutral envelope protocol shared by the Vue browser client
(libsodium-wrappers-sumo) and the Go CLI (golang.org/x/crypto). Laravel treats
every envelope as opaque ciphertext and never receives a team key, decrypted
value, search term, filename, MIME type, or plaintext file byte.

## Cryptographic primitives

- X25519 member encryption keys (32-byte shares).
- Argon2id for recovery-phrase key derivation (`m=64 MiB, t=2, p=1`, 32-byte
  output).
- HKDF-SHA-256 for subkey derivation.
- XChaCha20-Poly1305 (16-byte tag, 24-byte / 192-bit nonce) for record payloads
  and key wrapping.
- Each key epoch has a fresh random 256-bit team data key.

## Envelope v1 (record)

JSON with base64url payload fields:

```json
{
  "v": 1,
  "alg": "xchacha20poly1305",
  "kid": 7,
  "rid": "note:42",
  "ver": 3,
  "n":  "<nonce, 24 bytes>",
  "ct": "<ciphertext + 16-byte tag>"
}
```

AEAD additional data (canonical ASCII):

```
e2ee:v1:{teamId}:{rid}:{kid}:{ver}
```

`rid` is `<record-type>:<id>`. The AAD binds team, record type/id, key epoch,
and optimistic version. A fresh random nonce is used for every encryption.

## Key envelope v1 (wrapped team key)

```json
{
  "v": 1,
  "alg": "x25519+hkdf+xchacha20poly1305",
  "epk": "<ephemeral X25519 public key>",
  "n":   "<nonce, 24 bytes>",
  "ct":  "<wrapped 32-byte team key + tag>"
}
```

Wrap: ephemeral X25519 keypair; shared = X25519(ephemeral secret, recipient
public); wrap key = HKDF-SHA-256(shared, salt=`e2ee-keywrap-v1`,
info=`team-key-wrap`); seal the team key with XChaCha20-Poly1305. Only the
opaque envelope is stored on Laravel.

## Recovery

BIP-0039 24-word mnemonic (English wordlist, 256-bit entropy + 8-bit checksum).
Recovery identity = Argon2id(phrase, salt=`e2ee-recovery-v1:{teamId}`). Member
secret key = HKDF-SHA-256(identity, info=`e2ee-member-v1:{userSalt}`); public
key from X25519 base multiplication. The member public key is the only thing
Laravel stores.

## Repository layout

- `e2ee/test-vectors.json`: frozen, deterministic cross-runtime vectors.
- `cli/crypto/`: Go reference implementation and vector tests.
- `resources/js/lib/e2ee/`: browser implementation and node vector tests.

Laravel never holds the private keys, team data keys, or any plaintext payload.
