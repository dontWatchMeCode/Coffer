---
id: 206
section: backend
status: planning
severity: medium
---

# Digital Asset Management (DAM)

Add DAM support for documents, images, and videos.

## Current State

- No file/media upload or asset management exists
- Record types are text/structured data only
- No storage integration (S3, local disk, etc.) configured for user uploads

## Scope

- Upload, store, organize, and retrieve digital assets (docs, images, videos)
- Asset metadata: name, type, size, dimensions/duration, description, tags
- Integration with existing record types (e.g., attach assets to tasks, notes, contacts)
- Preview/serve images and videos; thumbnails for documents

## Planning Work

1. Audit storage needs: file size limits, mime types, CDN/preview requirements
2. Design asset model: `Asset` or extend existing record types with file fields
3. Determine storage driver: local + S3, direct upload, signed URLs
4. Design UI: asset picker, gallery view, drag-and-drop upload, file preview
5. Consider video transcoding / image optimization pipeline
6. Check existing Laravel ecosystem tools (Laravel MediaLibrary, Spatie Media Library, etc.)

## Open Questions

- Should assets be a new Record type or a polymorphic attachment on existing records?
- What's the max upload size? Video length limit?
- Need image optimization (responsive srcsets, WebP/AVIF)?
- Need video transcoding? If so, what codec/resolution?
- Use Spatie Media Library or build custom?
- Public vs team-scoped assets?

## Acceptance Criteria

- [ ] Upload documents, images, and videos
- [ ] Assets attachable to existing records (tasks, notes, contacts, etc.)
- [ ] File preview (images/videos inline, document thumbnail)
- [ ] Basic metadata + searchable via Scout
- [ ] Storage and access are team-scoped
