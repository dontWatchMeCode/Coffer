// Package crypto implements the shared E2EE vault envelope protocol used by
// the browser (libsodium-wrappers-sumo) and the Go CLI.
//
// Envelope v1 (JSON, base64url payload fields):
//
//	{
//	  "v": 1,
//	  "alg": "xchacha20poly1305",
//	  "kid": 12,
//	  "rid": "note:42",
//	  "ver": 3,
//	  "n":  "...24-byte nonce, base64url...",
//	  "ct": "...ciphertext+16-byte tag, base64url..."
//	}
//
// The AEAD additional data is the canonical ASCII string
// "e2ee:v1:{teamId}:{rid}:{kid}:{ver}" so every runtime derives the same AAD
// from public metadata. Laravel never receives any plaintext value, key,
// search term, filename, MIME type, or file byte.
//
// Key wrapping to a member public key (X25519) uses an ephemeral X25519 key,
// HKDF-SHA-256 derived wrap key, and XChaCha20-Poly1305:
//
//	{
//	  "v": 1,
//	  "alg": "x25519+hkdf+xchacha20poly1305",
//	  "epk": "...ephemeral public key, base64url...",
//	  "n":   "...24-byte nonce, base64url...",
//	  "ct":  "...wrapped key + tag, base64url..."
//	}
//
// Recovery derives a 256-bit member identity from a BIP-0039 mnemonic phrase
// via Argon2id. The member encryption keypair is derived from that identity
// via HKDF-SHA-256 and X25519.
package crypto

import (
	"crypto/hkdf"
	"crypto/rand"
	"crypto/sha256"
	"crypto/subtle"
	"encoding/base64"
	"encoding/json"
	"errors"
	"math/big"
	"strconv"
	"strings"

	"golang.org/x/crypto/argon2"
	"golang.org/x/crypto/chacha20poly1305"
	"golang.org/x/crypto/curve25519"
)

// Protocol constants. Keep in sync with docs/e2ee/protocol.md.
const (
	EnvelopeVersion  = 1
	RecordAlg        = "xchacha20poly1305"
	KeyWrapAlg       = "x25519+hkdf+xchacha20poly1305"
	KeyWrapSalt      = "e2ee-keywrap-v1"
	RecoverySalt     = "e2ee-recovery-v1"
	MemberInfo       = "e2ee-member-v1"
	NonceSize        = chacha20poly1305.NonceSizeX // 24 bytes, 192-bit
	KeySize          = 32
	PublicKeySize    = 32
	SecretKeySize    = 32
	ChunkSize        = 64 * 1024
	PhraseWordCount  = 24
	PhraseWordBits   = 11
	PhraseCheckBits  = 8
)

var (
	ErrMalformed      = errors.New("vault: malformed envelope")
	ErrVersion        = errors.New("vault: unsupported envelope version")
	ErrAlg            = errors.New("vault: unsupported envelope algorithm")
	ErrDecrypt        = errors.New("vault: decryption failed")
	ErrWordCount      = errors.New("vault: recovery phrase must contain 24 words")
	ErrUnknownWord    = errors.New("vault: recovery phrase contains a word outside the wordlist")
	ErrChecksum       = errors.New("vault: recovery phrase checksum mismatch")
	ErrWrongIdentity  = errors.New("vault: member identity does not match public key")
	ErrEntropySize    = errors.New("vault: recovery entropy must be 32 bytes")
)

// PublicKey is a 32-byte X25519 member public key.
type PublicKey [PublicKeySize]byte

// SecretKey is a 32-byte X25519 member secret key.
type SecretKey [SecretKeySize]byte

// TeamKey is the random 256-bit team data key for one key epoch.
type TeamKey [KeySize]byte

// RecordEnvelope is the versioned, opaque ciphertext envelope stored on the
// server. Every field is operational metadata; the payload is ciphertext.
type RecordEnvelope struct {
	Version int    `json:"v"`
	Alg     string `json:"alg"`
	Kid     int64  `json:"kid"`
	Rid     string `json:"rid"`
	Ver     int64  `json:"ver"`
	Nonce   string `json:"n"`
	Ct      string `json:"ct"`
}

// KeyEnvelope is the wrapped team data key for one member public key.
type KeyEnvelope struct {
	Version int    `json:"v"`
	Alg     string `json:"alg"`
	Epk     string `json:"epk"`
	Nonce   string `json:"n"`
	Ct      string `json:"ct"`
}

// AAD builds the canonical AEAD additional data string.
func AAD(teamID int64, rid string, kid, ver int64) string {
	return "e2ee:v1:" + itoa(teamID) + ":" + rid + ":" + itoa(kid) + ":" + itoa(ver)
}

// GenerateTeamKey returns a fresh random 256-bit team data key.
func GenerateTeamKey() (TeamKey, error) {
	var key TeamKey
	if _, err := rand.Read(key[:]); err != nil {
		return TeamKey{}, err
	}
	return key, nil
}

// GenerateSecretKey returns a fresh random X25519 secret key.
func GenerateSecretKey() (SecretKey, error) {
	var secret SecretKey
	if _, err := rand.Read(secret[:]); err != nil {
		return SecretKey{}, err
	}
	return secret, nil
}

// PublicFromSecret derives the X25519 public key for a secret key.
func PublicFromSecret(secret *SecretKey) (PublicKey, error) {
	pk, err := curve25519.X25519(secret[:], curve25519.Basepoint)
	if err != nil {
		return PublicKey{}, err
	}
	var public PublicKey
	copy(public[:], pk)
	return public, nil
}

func b64(data []byte) string {
	return base64.RawURLEncoding.EncodeToString(data)
}

func unb64(s string) ([]byte, error) {
	return base64.RawURLEncoding.DecodeString(s)
}

func itoa(v int64) string {
	return strconv.FormatInt(v, 10)
}

// EncryptRecord seals a plaintext payload into a RecordEnvelope with a fresh
// random 192-bit nonce. The AAD binds team, record id, key epoch, and version.
func EncryptRecord(teamID int64, teamKey *TeamKey, kid int64, rid string, ver int64, plaintext []byte) (*RecordEnvelope, error) {
	nonce := make([]byte, NonceSize)
	if _, err := rand.Read(nonce); err != nil {
		return nil, err
	}
	return encryptRecordWithNonce(teamID, teamKey, kid, rid, ver, plaintext, nonce)
}

// encryptRecordWithNonce seals a payload with an explicit nonce. Exported for
// reproducible test vectors only; production callers use EncryptRecord.
func encryptRecordWithNonce(teamID int64, teamKey *TeamKey, kid int64, rid string, ver int64, plaintext, nonce []byte) (*RecordEnvelope, error) {
	aead, err := chacha20poly1305.NewX(teamKey[:])
	if err != nil {
		return nil, err
	}
	aad := []byte(AAD(teamID, rid, kid, ver))
	ct := aead.Seal(nil, nonce, plaintext, aad)
	return &RecordEnvelope{
		Version: EnvelopeVersion,
		Alg:     RecordAlg,
		Kid:     kid,
		Rid:     rid,
		Ver:     ver,
		Nonce:   b64(nonce),
		Ct:      b64(ct),
	}, nil
}

// EncryptRecordDeterministic seals a payload with a fixed nonce for test
// vectors. Production code must use EncryptRecord.
func EncryptRecordDeterministic(teamID int64, teamKey *TeamKey, kid int64, rid string, ver int64, plaintext, nonce []byte) (*RecordEnvelope, error) {
	return encryptRecordWithNonce(teamID, teamKey, kid, rid, ver, plaintext, nonce)
}

// DecryptRecord opens a RecordEnvelope and verifies the bound AAD.
func DecryptRecord(teamID int64, teamKey *TeamKey, envelope *RecordEnvelope) ([]byte, error) {
	if envelope.Version != EnvelopeVersion {
		return nil, ErrVersion
	}
	if envelope.Alg != RecordAlg {
		return nil, ErrAlg
	}
	aead, err := chacha20poly1305.NewX(teamKey[:])
	if err != nil {
		return nil, err
	}
	nonce, err := unb64(envelope.Nonce)
	if err != nil || len(nonce) != NonceSize {
		return nil, ErrMalformed
	}
	ct, err := unb64(envelope.Ct)
	if err != nil || len(ct) < aead.Overhead() {
		return nil, ErrMalformed
	}
	aad := []byte(AAD(teamID, envelope.Rid, envelope.Kid, envelope.Ver))
	plaintext, err := aead.Open(nil, nonce, ct, aad)
	if err != nil {
		return nil, ErrDecrypt
	}
	return plaintext, nil
}

// WrapTeamKey wraps a team data key to a member X25519 public key using an
// ephemeral X25519 key, HKDF-SHA-256 derived wrap key, and XChaCha20-Poly1305.
// Only the returned opaque envelope is stored on Laravel.
func WrapTeamKey(teamKey *TeamKey, recipient *PublicKey) (*KeyEnvelope, error) {
	ephemeralSecret, err := GenerateSecretKey()
	if err != nil {
		return nil, err
	}
	nonce := make([]byte, NonceSize)
	if _, err := rand.Read(nonce); err != nil {
		return nil, err
	}
	return wrapTeamKeyWithEphemeral(teamKey, recipient, &ephemeralSecret, nonce)
}

// wrapTeamKeyWithEphemeral wraps a team key with an explicit ephemeral secret
// and nonce. Exported as WrapTeamKeyDeterministic for reproducible vectors.
func wrapTeamKeyWithEphemeral(teamKey *TeamKey, recipient *PublicKey, ephemeralSecret *SecretKey, nonce []byte) (*KeyEnvelope, error) {
	ephemeralPublic, err := PublicFromSecret(ephemeralSecret)
	if err != nil {
		return nil, err
	}
	shared, err := curve25519.X25519(ephemeralSecret[:], recipient[:])
	if err != nil {
		return nil, err
	}
	wrapKey := hkdfSHA256(shared, []byte(KeyWrapSalt), []byte("team-key-wrap"))
	aead, err := chacha20poly1305.NewX(wrapKey)
	if err != nil {
		return nil, err
	}
	ct := aead.Seal(nil, nonce, teamKey[:], nil)
	return &KeyEnvelope{
		Version: EnvelopeVersion,
		Alg:     KeyWrapAlg,
		Epk:     b64(ephemeralPublic[:]),
		Nonce:   b64(nonce),
		Ct:      b64(ct),
	}, nil
}

// WrapTeamKeyDeterministic wraps a team key with fixed ephemeral material for
// test vectors. Production code must use WrapTeamKey.
func WrapTeamKeyDeterministic(teamKey *TeamKey, recipient *PublicKey, ephemeralSecret *SecretKey, nonce []byte) (*KeyEnvelope, error) {
	return wrapTeamKeyWithEphemeral(teamKey, recipient, ephemeralSecret, nonce)
}

// UnwrapTeamKey opens a KeyEnvelope with the member's X25519 secret key.
func UnwrapTeamKey(secret *SecretKey, envelope *KeyEnvelope) (TeamKey, error) {
	var teamKey TeamKey
	if envelope.Version != EnvelopeVersion {
		return teamKey, ErrVersion
	}
	if envelope.Alg != KeyWrapAlg {
		return teamKey, ErrAlg
	}
	epk, err := unb64(envelope.Epk)
	if err != nil || len(epk) != PublicKeySize {
		return teamKey, ErrMalformed
	}
	shared, err := curve25519.X25519(secret[:], epk)
	if err != nil {
		return teamKey, err
	}
	wrapKey := hkdfSHA256(shared, []byte(KeyWrapSalt), []byte("team-key-wrap"))
	aead, err := chacha20poly1305.NewX(wrapKey)
	if err != nil {
		return teamKey, err
	}
	nonce, err := unb64(envelope.Nonce)
	if err != nil || len(nonce) != NonceSize {
		return teamKey, ErrMalformed
	}
	ct, err := unb64(envelope.Ct)
	if err != nil || len(ct) < aead.Overhead() {
		return teamKey, ErrMalformed
	}
	opened, err := aead.Open(nil, nonce, ct, nil)
	if err != nil {
		return teamKey, ErrDecrypt
	}
	if len(opened) != KeySize {
		return teamKey, ErrMalformed
	}
	copy(teamKey[:], opened)
	return teamKey, nil
}

// DeriveMemberSecret derives a 32-byte X25519 member secret from a recovery
// identity and a user-scoped info salt.
func DeriveMemberSecret(identity []byte, userSalt string) (SecretKey, error) {
	var secret SecretKey
	material := hkdfSHA256(identity, nil, []byte(MemberInfo+":"+userSalt))
	if len(material) != SecretKeySize {
		return secret, ErrMalformed
	}
	copy(secret[:], material)
	return secret, nil
}

// DeriveRecoveryIdentity hashes a recovery phrase with Argon2id to a 256-bit
// identity. Laravel never sees the phrase or the identity.
func DeriveRecoveryIdentity(phrase string, teamIDSalt string) ([]byte, error) {
	normalized, err := NormalizePhrase(phrase)
	if err != nil {
		return nil, err
	}
	return argon2.IDKey([]byte(normalized), []byte(RecoverySalt+":"+teamIDSalt), 2, 64*1024, 1, 32), nil
}

// VerifyMemberPublicKey checks an identity-derived secret against an expected
// public key in constant time.
func VerifyMemberPublicKey(secret *SecretKey, expected *PublicKey) error {
	derived, err := PublicFromSecret(secret)
	if err != nil {
		return err
	}
	if subtle.ConstantTimeCompare(derived[:], expected[:]) != 1 {
		return ErrWrongIdentity
	}
	return nil
}

func hkdfSHA256(secret, salt, info []byte) []byte {
	out, err := hkdf.Key(sha256.New, secret, salt, string(info), KeySize)
	if err != nil {
		panic(err) // inputs are fixed-size; cannot fail
	}
	return out
}

// ---- Envelope JSON encoding ----

// Encode marshals a record envelope to its canonical JSON form.
func (e *RecordEnvelope) Encode() ([]byte, error) {
	return json.Marshal(e)
}

// DecodeRecordEnvelope parses a canonical JSON record envelope.
func DecodeRecordEnvelope(raw []byte) (*RecordEnvelope, error) {
	var envelope RecordEnvelope
	if err := json.Unmarshal(raw, &envelope); err != nil {
		return nil, ErrMalformed
	}
	if envelope.Version != EnvelopeVersion || envelope.Alg != RecordAlg {
		return nil, ErrVersion
	}
	return &envelope, nil
}

// Encode marshals a key envelope to its canonical JSON form.
func (e *KeyEnvelope) Encode() ([]byte, error) {
	return json.Marshal(e)
}

// DecodeKeyEnvelope parses a canonical JSON key envelope.
func DecodeKeyEnvelope(raw []byte) (*KeyEnvelope, error) {
	var envelope KeyEnvelope
	if err := json.Unmarshal(raw, &envelope); err != nil {
		return nil, ErrMalformed
	}
	if envelope.Version != EnvelopeVersion || envelope.Alg != KeyWrapAlg {
		return nil, ErrVersion
	}
	return &envelope, nil
}

// ---- BIP-0039 recovery phrase ----

// GeneratePhrase returns a fresh 24-word BIP-0039 mnemonic encoding 256 bits
// of entropy with an 8-bit SHA-256 checksum.
func GeneratePhrase() (string, error) {
	entropy := make([]byte, 32)
	if _, err := rand.Read(entropy); err != nil {
		return "", err
	}
	return EntropyToPhrase(entropy)
}

// EntropyToPhrase encodes 32 bytes of entropy as a BIP-0039 mnemonic.
func EntropyToPhrase(entropy []byte) (string, error) {
	if len(entropy) != 32 {
		return "", ErrEntropySize
	}
	hash := sha256.Sum256(entropy)
	bits := new(big.Int).SetBytes(entropy)
	bits.Lsh(bits, PhraseCheckBits)
	bits.Or(bits, big.NewInt(int64(hash[0]>>(8-PhraseCheckBits))))

	words := make([]string, PhraseWordCount)
	for i := PhraseWordCount - 1; i >= 0; i-- {
		index := new(big.Int).And(bits, big.NewInt((1<<PhraseWordBits)-1)).Int64()
		words[i] = bip39EnglishWordlist[index]
		bits.Rsh(bits, PhraseWordBits)
	}
	return strings.Join(words, " "), nil
}

// PhraseToEntropy decodes a BIP-0039 mnemonic and verifies its checksum,
// returning the 32-byte entropy.
func PhraseToEntropy(phrase string) ([]byte, error) {
	words := strings.Fields(strings.ToLower(strings.TrimSpace(phrase)))
	if len(words) != PhraseWordCount {
		return nil, ErrWordCount
	}
	bits := new(big.Int)
	for _, word := range words {
		idx, ok := wordIndex[word]
		if !ok {
			return nil, ErrUnknownWord
		}
		bits.Lsh(bits, PhraseWordBits)
		bits.Or(bits, big.NewInt(int64(idx)))
	}
	entropySize := (PhraseWordCount*PhraseWordBits - PhraseCheckBits) / 8
	checksum := new(big.Int).And(bits, big.NewInt((1<<PhraseCheckBits)-1)).Int64()

	// Drop the trailing checksum bits to recover the entropy.
	entropyBig := new(big.Int).Rsh(bits, PhraseCheckBits)
	entropy := entropyBig.Bytes()

	// Left-pad to entropySize bytes in case the leading bytes are zero.
	if len(entropy) < entropySize {
		padded := make([]byte, entropySize)
		copy(padded[entropySize-len(entropy):], entropy)
		entropy = padded
	}
	if len(entropy) != entropySize {
		return nil, ErrChecksum
	}
	hash := sha256.Sum256(entropy)
	expected := int64(hash[0] >> (8 - PhraseCheckBits))
	if checksum != expected {
		return nil, ErrChecksum
	}
	return entropy, nil
}

// NormalizePhrase validates and canonically normalizes a recovery phrase.
func NormalizePhrase(phrase string) (string, error) {
	words := strings.Fields(strings.ToLower(strings.TrimSpace(phrase)))
	if len(words) != PhraseWordCount {
		return "", ErrWordCount
	}
	for _, word := range words {
		if _, ok := wordIndex[word]; !ok {
			return "", ErrUnknownWord
		}
	}
	return strings.Join(words, " "), nil
}

// wordIndex maps wordlist entries to their 11-bit index.
var wordIndex map[string]uint16

func init() {
	wordIndex = make(map[string]uint16, len(bip39EnglishWordlist))
	for i, word := range bip39EnglishWordlist {
		wordIndex[word] = uint16(i)
	}
}