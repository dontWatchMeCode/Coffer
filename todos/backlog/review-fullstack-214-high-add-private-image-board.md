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
- Validate uploads with large-media-ready limits. Support images first, keep the validation design extensible for documents and videos, and do not allow SVG.

## Frontend Notes

- Add `@tanstack/vue-virtual` as an explicit dependency, even though it is currently transitive through `reka-ui`.
- Use private inline and download route URLs in payloads, for example `previewUrl` and `downloadUrl`, rather than public storage URLs.
- Use lazy `<img>` loading and a visual placeholder/skeleton while image previews load.
- Adapt image board lane count responsively for mobile, tablet, and desktop.
- Keep the UI consistent with existing list pages and Notes view switching, but make the image board more visual and skimmable like Pinry.
- Non-image future file types should be able to render as generic text/file cards without schema or route rewrites.

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
