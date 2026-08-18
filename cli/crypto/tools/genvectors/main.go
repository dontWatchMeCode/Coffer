// Command genvectors emits the canonical cross-runtime test fixture used by
// both the Go CLI and the Vue browser implementation. Inputs are fixed and the
// AEAD operations use frozen nonces/ephemeral keys so the fixture is
// deterministic and frozen.
package main

import (
	"encoding/base64"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"log"
	"os"

	"dashboard.team/cli/crypto"
)

func fromB64(s string) []byte {
	b, err := base64.RawURLEncoding.DecodeString(s)
	if err != nil {
		log.Fatal(err)
	}
	return b
}

func main() {
	teamKey := mustTeamKey("000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f")
	nonce := mustHex("000000000000000000000000000000000000000000000000")
	record := mustHex("7b227469746c65223a2248656c6c6f2045653265227d") // {"title":"Hello Ee2e"}
	kid, ver, teamID := int64(7), int64(3), int64(11)
	rid := "note:42"

	// BIP39 phrase from 32 bytes of zero entropy: "abandon" x23 + checksum word.
	entropy := mustHex("0000000000000000000000000000000000000000000000000000000000000000")
	phrase, err := crypto.EntropyToPhrase(entropy)
	if err != nil {
		log.Fatal(err)
	}

	identity := mustDerive(phrase, "11")
	memberSecret, err := crypto.DeriveMemberSecret(identity, "user-1")
	if err != nil {
		log.Fatal(err)
	}
	memberPublic, err := crypto.PublicFromSecret(&memberSecret)
	if err != nil {
		log.Fatal(err)
	}

	// Wrap the team key to the member public key with frozen ephemeral material.
	wrapSecret := secretFromHex("202122232425262728292a2b2c2d2e2f303132333435363738393a3b3c3d3e3f")
	wrapNonce := mustHex("ac9e526d8bebf2dcb30309e8fbfc6a80a9c7d32a612fc68d")
	wrap, err := crypto.WrapTeamKeyDeterministic(&teamKey, &memberPublic, &wrapSecret, wrapNonce)
	if err != nil {
		log.Fatal(err)
	}

	// Encrypt the record with a frozen nonce.
	envelope, err := crypto.EncryptRecordDeterministic(teamID, &teamKey, kid, rid, ver, record, nonce)
	if err != nil {
		log.Fatal(err)
	}

	v := map[string]any{
		"team_id":           teamID,
		"team_key":          hex.EncodeToString(teamKey[:]),
		"kid":               kid,
		"rid":               rid,
		"ver":               ver,
		"aad":               crypto.AAD(teamID, rid, kid, ver),
		"plaintext":         hex.EncodeToString(record),
		"nonce":             hex.EncodeToString(fromB64(envelope.Nonce)),
		"ciphertext":        hex.EncodeToString(fromB64(envelope.Ct)),
		"phrase":            phrase,
		"phrase_entropy":    hex.EncodeToString(entropy),
		"recovery_identity": hex.EncodeToString(identity),
		"member_secret":     hex.EncodeToString(memberSecret[:]),
		"member_public":     hex.EncodeToString(memberPublic[:]),
		"recipient_public":  hex.EncodeToString(memberPublic[:]),
		"wrap_ephemeral":    hex.EncodeToString(fromB64(wrap.Epk)),
		"wrap_nonce":        hex.EncodeToString(fromB64(wrap.Nonce)),
		"wrap_ciphertext":   hex.EncodeToString(fromB64(wrap.Ct)),
	}

	out, err := json.MarshalIndent(v, "", "  ")
	if err != nil {
		log.Fatal(err)
	}
	path := "/home/me/Projects/dashboard/e2ee/test-vectors.json"
	if err := os.WriteFile(path, append(out, '\n'), 0o644); err != nil {
		log.Fatal(err)
	}
	fmt.Printf("wrote %s\n", path)
}

func mustTeamKey(hexKey string) crypto.TeamKey {
	var key crypto.TeamKey
	copy(key[:], mustHex(hexKey))
	return key
}

func secretFromHex(hexKey string) crypto.SecretKey {
	var secret crypto.SecretKey
	b := mustHex(hexKey)
	if len(b) != crypto.SecretKeySize {
		log.Fatalf("secret must be %d bytes", crypto.SecretKeySize)
	}
	copy(secret[:], b)
	return secret
}

func mustDerive(phrase, teamIDSalt string) []byte {
	identity, err := crypto.DeriveRecoveryIdentity(phrase, teamIDSalt)
	if err != nil {
		log.Fatal(err)
	}
	return identity
}

func mustHex(s string) []byte {
	b, err := hex.DecodeString(s)
	if err != nil {
		log.Fatal(err)
	}
	return b
}

