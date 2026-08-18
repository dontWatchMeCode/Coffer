package crypto

import (
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"os"
	"path/filepath"
	"testing"
)

type vectorFixture struct {
	TeamID           int64  `json:"team_id"`
	TeamKey          string `json:"team_key"`
	Kid              int64  `json:"kid"`
	Rid              string `json:"rid"`
	Ver              int64  `json:"ver"`
	AAD              string `json:"aad"`
	Plaintext        string `json:"plaintext"`
	Nonce            string `json:"nonce"`
	Ciphertext       string `json:"ciphertext"`
	Phrase           string `json:"phrase"`
	PhraseEntropy    string `json:"phrase_entropy"`
	RecoveryIdentity string `json:"recovery_identity"`
	MemberSecret     string `json:"member_secret"`
	MemberPublic     string `json:"member_public"`
	RecipientPublic  string `json:"recipient_public"`
	WrapEphemeral    string `json:"wrap_ephemeral"`
	WrapNonce        string `json:"wrap_nonce"`
	WrapCiphertext   string `json:"wrap_ciphertext"`
}

func loadVectors(t *testing.T) vectorFixture {
	t.Helper()
	path := filepath.Join("..", "..", "e2ee", "test-vectors.json")
	raw, err := os.ReadFile(path)
	if err != nil {
		t.Fatalf("read vectors: %v", err)
	}
	var v vectorFixture
	if err := json.Unmarshal(raw, &v); err != nil {
		t.Fatalf("parse vectors: %v", err)
	}
	return v
}

func TestVectorsRecordEncryption(t *testing.T) {
	v := loadVectors(t)

	var teamKey TeamKey
	teamKeyBytes := decodeHex(v.TeamKey)
	copy(teamKey[:], teamKeyBytes)

	envelope := &RecordEnvelope{
		Version: EnvelopeVersion,
		Alg:     RecordAlg,
		Kid:     v.Kid,
		Rid:     v.Rid,
		Ver:     v.Ver,
		Nonce:   b64(decodeHex(v.Nonce)),
		Ct:      b64(decodeHex(v.Ciphertext)),
	}

	plaintext, err := DecryptRecord(v.TeamID, &teamKey, envelope)
	if err != nil {
		t.Fatalf("decrypt: %v", err)
	}
	if hex.EncodeToString(plaintext) != v.Plaintext {
		t.Fatalf("plaintext mismatch:\n got %s\n want %s", hex.EncodeToString(plaintext), v.Plaintext)
	}

	if got := AAD(v.TeamID, v.Rid, v.Kid, v.Ver); got != v.AAD {
		t.Fatalf("aad mismatch: got %q want %q", got, v.AAD)
	}
}

func TestVectorsPhraseAndIdentity(t *testing.T) {
	v := loadVectors(t)

	entropy, err := PhraseToEntropy(v.Phrase)
	if err != nil {
		t.Fatalf("phrase to entropy: %v", err)
	}
	if hex.EncodeToString(entropy) != v.PhraseEntropy {
		t.Fatalf("entropy mismatch: %s != %s", hex.EncodeToString(entropy), v.PhraseEntropy)
	}

	phrase, err := EntropyToPhrase(entropy)
	if err != nil {
		t.Fatalf("entropy to phrase: %v", err)
	}
	if phrase != v.Phrase {
		t.Fatalf("phrase mismatch:\n got %q\n want %q", phrase, v.Phrase)
	}

	identity, err := DeriveRecoveryIdentity(v.Phrase, "11")
	if err != nil {
		t.Fatalf("derive identity: %v", err)
	}
	if hex.EncodeToString(identity) != v.RecoveryIdentity {
		t.Fatalf("identity mismatch:\n got %s\n want %s", hex.EncodeToString(identity), v.RecoveryIdentity)
	}

	secret, err := DeriveMemberSecret(identity, "user-1")
	if err != nil {
		t.Fatalf("derive member secret: %v", err)
	}
	if hex.EncodeToString(secret[:]) != v.MemberSecret {
		t.Fatalf("member secret mismatch:\n got %s\n want %s", hex.EncodeToString(secret[:]), v.MemberSecret)
	}

	public, err := PublicFromSecret(&secret)
	if err != nil {
		t.Fatalf("derive public: %v", err)
	}
	if hex.EncodeToString(public[:]) != v.MemberPublic {
		t.Fatalf("member public mismatch:\n got %s\n want %s", hex.EncodeToString(public[:]), v.MemberPublic)
	}
}

func TestVectorsKeyWrap(t *testing.T) {
	v := loadVectors(t)

	var teamKey TeamKey
	teamKeyBytes := decodeHex(v.TeamKey)
	copy(teamKey[:], teamKeyBytes)

	var memberPublic PublicKey
	copy(memberPublic[:], decodeHex(v.MemberPublic))

	envelope := &KeyEnvelope{
		Version: EnvelopeVersion,
		Alg:     KeyWrapAlg,
		Epk:     b64(decodeHex(v.WrapEphemeral)),
		Nonce:   b64(decodeHex(v.WrapNonce)),
		Ct:      b64(decodeHex(v.WrapCiphertext)),
	}

	// Unwrap with member's own secret key.
	var memberSecret SecretKey
	copy(memberSecret[:], decodeHex(v.MemberSecret))

	unwrapped, err := UnwrapTeamKey(&memberSecret, envelope)
	if err != nil {
		t.Fatalf("unwrap: %v", err)
	}
	if hex.EncodeToString(unwrapped[:]) != v.TeamKey {
		t.Fatalf("unwrapped team key mismatch: %s != %s", hex.EncodeToString(unwrapped[:]), v.TeamKey)
	}
}

func TestRoundTripRecord(t *testing.T) {
	var teamKey TeamKey
	if _, err := rand.Read(teamKey[:]); err != nil {
		t.Fatal(err)
	}
	plaintext := []byte(`{"title":"Secret","tags":["a"]}`)
	env, err := EncryptRecord(11, &teamKey, 1, "note:1", 2, plaintext)
	if err != nil {
		t.Fatal(err)
	}

	got, err := DecryptRecord(11, &teamKey, env)
	if err != nil {
		t.Fatalf("decrypt: %v", err)
	}
	if string(got) != string(plaintext) {
		t.Fatalf("roundtrip mismatch: %s", got)
	}
}

func TestTamperDetected(t *testing.T) {
	var teamKey TeamKey
	if _, err := rand.Read(teamKey[:]); err != nil {
		t.Fatal(err)
	}
	plaintext := []byte(`{"title":"Secret"}`)
	env, err := EncryptRecord(11, &teamKey, 1, "note:1", 2, plaintext)
	if err != nil {
		t.Fatal(err)
	}
	ct := decodeB64(t, env.Ct)
	ct[8] ^= 0xff
	env.Ct = stdB64(ct)

	if _, err := DecryptRecord(11, &teamKey, env); err != ErrDecrypt {
		t.Fatalf("expected ErrDecrypt, got %v", err)
	}
}

func TestWrongKeyFails(t *testing.T) {
	var key1, key2 TeamKey
	if _, err := rand.Read(key1[:]); err != nil {
		t.Fatal(err)
	}
	if _, err := rand.Read(key2[:]); err != nil {
		t.Fatal(err)
	}
	env, err := EncryptRecord(11, &key1, 1, "note:1", 2, []byte("x"))
	if err != nil {
		t.Fatal(err)
	}
	if _, err := DecryptRecord(11, &key2, env); err != ErrDecrypt {
		t.Fatalf("expected ErrDecrypt with wrong key, got %v", err)
	}
}

func decodeHex(s string) []byte {
	b, err := hex.DecodeString(s)
	if err != nil {
		panic(err)
	}
	return b
}

func decodeB64(t *testing.T, s string) []byte {
	t.Helper()
	b, err := unb64(s)
	if err != nil {
		t.Fatal(err)
	}
	return b
}

func stdB64(data []byte) string {
	return b64(data)
}