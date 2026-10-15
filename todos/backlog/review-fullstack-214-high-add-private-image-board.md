---
id: 214
section: fullstack
status: review
severity: high
---

# Add Private Files Library

Add a private Files library for team-scoped uploads with an image-first board, text-only list/card views, existing tags, and existing record linking. Build the foundation to support future media types such as documents and videos without exposing public file URLs.

## Context

- Reference for image board view: https://github.com/pinry/pinry
- Files must be uploads only.
- Uploaded files must not be public links or public storage assets.
- The first implementation should support images, while the data model, routes, and UI naming stay file/media agnostic.
- The board should reuse existing record patterns from Notes, Bookmarks, Collections, tags, links, activity history, team features, and Wayfinder.

## Scope

1. Create a generic file/media record type, such as `FileItem` or `MediaItem`, with team ownership, policy coverage, soft deletes, tags, links, activity history, and search registration.
2. Store uploaded files on the private `local` disk under a generic private path such as `storage/app/private/files/...`.
3. Store generic file metadata in the database: `disk`, `path`, `original_name`, `mime_type`, `size`, plus `title` and optional `description`.
4. Store type-specific metadata without making all files image-only, for example nullable `width` and `height` for images and future preview metadata for videos/documents.
5. Serve file bytes only through authenticated team-scoped routes, for example inline preview and download routes under `/{current_team}/files/{file}`.
6. Return private inline and download responses with policy/team checks and `Cache-Control: private`.
7. Add Inertia pages for files index, item detail, trash, create/upload, edit, delete, restore, and force delete.
8. Build an image-first Pinry-style board using `@tanstack/vue-virtual` with `useVirtualizer`, `lanes`, `measureElement`, and overscan.
9. Add a text-only card/list view switch consistent with the Notes view switch styles.
10. Use Inertia `<InfiniteScroll>` for endless page fetching and TanStack Virtual for image board DOM windowing.
11. Add existing tag and record-link panels to file detail pages.
12. Add sidebar navigation as `Files`, team feature toggle support, search/link candidate support, and regenerated Wayfinder routes.

## Security Requirements

- Do not use the `public` disk for file uploads.
- Do not expose `asset('storage/...')` URLs or require `storage:link` for Files.
- Guests cannot view file metadata pages, inline previews, downloads, or raw bytes.
- Authenticated non-members cannot view file metadata pages, inline previews, downloads, or raw bytes.
- Soft-deleted records should not expose file bytes through normal show/inline/download routes.
- Force delete must remove the private uploaded file and clean up tags/links.
- Validate uploads via centralized `FileTypes` upload/preview policy with same 100 MB HTTP+MCP limit. Accepts JPEG/PNG/GIF/WebP/MP4/WebM/PDF/TXT/DOCX/XLSX/PPTX; rejects SVG/HTML/XML. Inline allowlist is images/video only; PDF/Office download-only per explicit user decision (no PDF.js, no Composer/NPM deps).

## Frontend Notes

- Add `@tanstack/vue-virtual` as an explicit dependency, even though it is currently transitive through `reka-ui`.
- Use private inline and download route URLs in payloads, for example `previewUrl` and `downloadUrl`, rather than public storage URLs.
- Use lazy `<img>` loading and a visual placeholder/skeleton while image previews load.
- Adapt image board lane count responsively for mobile, tablet, and desktop.
- Keep the UI consistent with existing list pages and Notes view switching, but make the image board more visual and skimmable like Pinry.
- Video cards show static JPEG previews from private authenticated `thumbnail` GET (no board `<video>`); lazy `<img>` with icon fallback on any image error. Detail page uses Video.js 10 `video-player`/`video-skin` with poster + `kind="metadata"` storyboard track. Docs/text remain icon placeholders (generic `FileItem` schema, no migrations). Measured virtual cards use stable id keys and `gap-16`.

## Recommended Work

1. Add migration, model, factory, and policy for generic file/media items.
2. Add requests/controllers/routes for CRUD, trash, private inline preview, and private download responses.
3. Wire record tags, record links, activity history, search registry, link helper, team features, and sidebar nav.
4. Create Vue types, pages, upload/delete dialogs, text-only list/card views, and virtual image masonry component.
5. Run `php artisan wayfinder:generate --no-interaction` after routes are added.
6. Add feature tests for upload, private inline/download access, team isolation, tags, links, trash, and force-delete file cleanup.
7. Run targeted tests, `vendor/bin/pint --dirty --format agent`, and frontend checks for touched Vue/TS files.

## Acceptance Criteria

- [x] Team members can upload image files with title and optional description.
- [x] File bytes are served only through authenticated team-scoped inline and download routes.
- [x] No public storage URLs are exposed in Inertia payloads or rendered preview/download links.
- [x] Files index supports an image preview board and a text-only card/list view switch consistent with Notes.
- [x] Image board renders a TanStack Virtual masonry grid with Inertia endless scroll.
- [x] File detail page supports existing tags and linked records.
- [x] File records participate in search and link candidate search.
- [x] Trash, restore, and permanent delete work.
- [x] Permanent delete removes the private uploaded file.
- [x] Guests and non-members cannot access pages, inline previews, downloads, or file bytes.
- [x] Targeted Pest tests pass.
- [x] Mixed board: video cards render static private JPEG thumbnails (no board video); docs/text use icon placeholders. Detail video uses Video.js 10 `video-player`/`video-skin` with poster + private `storyboard.vtt` hover track, `preload="metadata"` with private `BinaryFileResponse` range support and codec-download fallback.
- [x] TXT preview is detail-only, bounded 64 KiB UTF-8 escaped; PDFs/Office are download-only; stored MCP MIME metadata is immutable without validated byte replacement; team auth/private cache/`nosniff`+CSP retained.

## Implemented Media Extension Note

- `VideoThumbnail` service creates private cached JPEG (`<source>.thumbnail.jpg`) on first authenticated `thumbnail` GET: FFmpeg timeout 10 / threads 1 / forced format / file protocol; 15s lock + 12s block; 300s failure backoff; JPEG-validated UUID temp + atomic move; rechecks fresh filesystem + DB path; deleted on forceDelete/MCP byte replacement. FFmpeg is an approved system dep (Docker runtime + browser CI), no Composer/NPM deps.
- Verification: 7 thumbnail feature tests; 3 browser tests (static JPEG card + detail play/seek, mixed text XSS, gap); `npm` 40 passed; `composer qa` passed 158.34s incl. full suite + browser. QA-loop 3 passes (startup-failure exception + test; reactive-set claim disproved with watchEffect regression; clean). PHPStan `freshFileExists` documents impure IO + `clearstatcache` and passed.
- Detail player is Video.js 10 (`@videojs/html` `video-player`/`video-skin` in `FilePreview.vue`) with poster on the `video-player` element (not the inner native `video`) + `kind="metadata"` track wired to private `storyboardUrl` (detail payload only). Private `storyboard.vtt`/`storyboard.jpg` endpoints: team-scoped auth, `private`/`no-cache`/`nosniff`, max 30 tiles at 160x90; full layout stored in `.storyboard.json` with legacy duration-only fallback; corrupt metadata self-heals (delete + 404 for retry); 5s ffprobe / 20s ffmpeg timeouts; 45s lock + 32s block + 300s failure backoff. Cards keep static JPEGs; no public URLs.

## Follow-up: PDF Text Preview

- [ ] Add a bounded, escaped PDF text preview using Poppler's `pdftotext`, while retaining private downloads and no embedded PDF viewer.
- Add the system dependency to Docker and relevant CI jobs; enforce extraction timeout/output limits, team authorization, and cleanup of temporary or cached output.
- Show a download fallback when extraction fails or produces no text. Scanned/image-only PDFs require OCR, which is outside this follow-up.
- Not implemented yet; current PDF behavior remains download-only.

## QA Polish (post-review)

- [x] Raise dev and prod PHP upload limits to 100 MB via `.php.ini.d/uploads.ini` + `composer run dev` `PHP_INI_SCAN_DIR` override + Dockerfile `conf.d/uploads.ini`.
- [x] Surface accepted image types and max size in the upload dialog via `uploadConstraints` prop.
- [x] Auto-fill empty title from uploaded filename.
- [x] Add `X-Content-Type-Options: nosniff` to inline and download responses.
- [x] Make `store` atomic: clean up the disk file if `FileItem::create` throws.
- [x] Keep validation errors visible on file edit by using `preserveState: true` in `Show.vue` `submitEdit`.
- [x] Use typed `usePage<PageProps>()` consistently in files pages and dialogs.
- [x] Add tests for update, restore, soft-delete route, PATCH file-byte rejection, and PNG upload.
- [x] Report actual server upload limit in the `file.uploaded` validation message when PHP rejects an upload.
