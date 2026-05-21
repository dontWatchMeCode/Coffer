---
id: 24
section: frontend
status: todo
severity: medium
---

# Search Support for #<tagname> Tag Syntax

Extend search to recognize `#<tagname>` syntax and filter results by tags.

## Implementation Notes

- The existing prefix-based search system uses `<prefix>:` syntax (e.g. `g:` for logs) via `ParsesSearchPrefixes::parseSearchPrefix` which matches `/^([a-z]):\s*(.*)$/i`.
- `#` does not match `[a-z]`, so `#tagname` will be silently treated as plain query text — `ParsesSearchPrefixes` must be extended or a separate tag-parsing path added before the frontend can use it.
- Tag filter should act as a global filter applied on top of normal search results (not as its own category/prefix).
- Requires a backend query scope or endpoint for tag-based matching.

## Acceptance Criteria

- [ ] Typing `#<tagname>` in search (overlay + full page) filters results by tag
- [ ] Tag matches are highlighted or indicated in results
- [ ] Works alongside existing prefix searches
- [ ] Backend returns tag-matched results efficiently
- [ ] Tests cover tag search parsing and result filtering
