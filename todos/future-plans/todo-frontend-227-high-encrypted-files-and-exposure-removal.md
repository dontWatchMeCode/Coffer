---
id: 227
section: frontend
status: todo
severity: high
---

# Encrypted Files, Previews, Tags, And Server Exposure Removal

Encrypt file bytes in the browser, upload/download ciphertext, and remove plaintext server data exposure for E2EE teams.

## File And Record UX

- Encrypt file bytes before upload; decrypt after download before display/save.
- Derive image previews and file metadata in the browser.
- Tags, links, previews, sorting, filters, dashboards, insights from decrypted local projections.
- Replace server-provided titles, excerpts, previews, tag/link candidates, and list sorting.

## Server Exposure Removal For E2EE Teams

- No plaintext Inertia page props from feature page controllers.
- `RecordSearchService`, `Filterable`, tag/link candidates, activity diffs, dashboards, server MIME inspection, dimensions, inline previews, decrypted downloads are not served to E2EE teams.

## Acceptance Criteria

- [ ] Encrypted files upload, download, and render without server-side plaintext inspection
- [ ] Tags/links/previews/dashboards/insights work from decrypted local projections
- [ ] E2EE team page responses contain no decrypted fields
- [ ] Frontend tests cover encrypted file round-trips

## Notes

- Laravel must not inspect decrypted bytes; browser holds the only plaintext view.