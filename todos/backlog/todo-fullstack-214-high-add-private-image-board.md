---
id: 214
section: fullstack
status: todo
severity: high
---

# Add Private Image Board

Add a Pinry-style image board for team-scoped uploaded images with endless scroll, virtualized masonry layout, existing tags, and existing record linking.

## Context

- Reference: https://github.com/pinry/pinry
- Images must be uploads only.
- Uploaded images must not be public links or public storage assets.
- The board should reuse existing record patterns from Notes, Bookmarks, Collections, tags, links, activity history, team features, and Wayfinder.

## Scope

1. Create an `ImageBoardItem` record type with team ownership, policy coverage, soft deletes, tags, links, activity history, and search registration.
2. Store image files on the private `local` disk under `storage/app/private/image-board/...`.
3. Store image metadata in the database: `disk`, `path`, `original_name`, `mime_type`, `size`, `width`, `height`, plus `title` and optional `description`.
4. Serve images only through authenticated team-scoped routes, for example `GET /{current_team}/image-board/{item}/image`.
5. Return private inline image responses with policy/team checks and `Cache-Control: private`.
6. Add Inertia pages for board index, item detail, trash, create/upload, edit, delete, restore, and force delete.
7. Build a Pinry-style masonry board using `@tanstack/vue-virtual` with `useVirtualizer`, `lanes`, `measureElement`, and overscan.
8. Use Inertia `<InfiniteScroll>` for endless page fetching and TanStack Virtual for DOM windowing.
9. Add existing tag and record-link panels to image detail pages.
10. Add sidebar navigation, team feature toggle support, search/link candidate support, and regenerated Wayfinder routes.

## Security Requirements

- Do not use the `public` disk for image board uploads.
- Do not expose `asset('storage/...')` URLs or require `storage:link` for image board images.
- Guests cannot view image metadata pages or image bytes.
- Authenticated non-members cannot view image metadata pages or image bytes.
- Soft-deleted records should not expose image bytes through normal show/image routes.
- Force delete must remove the private image file and clean up tags/links.
- Validate uploads with Laravel image/file validation; do not allow SVG.

## Frontend Notes

- Add `@tanstack/vue-virtual` as an explicit dependency, even though it is currently transitive through `reka-ui`.
- Use private image route URLs in payloads, for example `imageUrl`, rather than public storage URLs.
- Use lazy `<img>` loading and a visual placeholder/skeleton while images load.
- Adapt lane count responsively for mobile, tablet, and desktop.
- Keep the UI consistent with existing list pages, but make the board more visual and skimmable like Pinry.

## Recommended Work

1. Add migration, model, factory, and policy for image board items.
2. Add requests/controllers/routes for CRUD, trash, and private image streaming.
3. Wire record tags, record links, activity history, search registry, link helper, team features, and sidebar nav.
4. Create Vue types, pages, upload/delete dialogs, and virtual masonry component.
5. Run `php artisan wayfinder:generate --no-interaction` after routes are added.
6. Add feature tests for upload, private image access, team isolation, tags, links, trash, and force-delete file cleanup.
7. Run targeted tests, `vendor/bin/pint --dirty --format agent`, and frontend checks for touched Vue/TS files.

## Acceptance Criteria

- [ ] Team members can upload images with title and optional description.
- [ ] Image bytes are served only through authenticated team-scoped routes.
- [ ] No public storage URLs are exposed in Inertia payloads or rendered image sources.
- [ ] Board index renders a TanStack Virtual masonry grid with Inertia endless scroll.
- [ ] Image detail page supports existing tags and linked records.
- [ ] Image board items participate in search and link candidate search.
- [ ] Trash, restore, and permanent delete work.
- [ ] Permanent delete removes the private uploaded file.
- [ ] Guests and non-members cannot access pages or image bytes.
- [ ] Targeted Pest tests pass.
