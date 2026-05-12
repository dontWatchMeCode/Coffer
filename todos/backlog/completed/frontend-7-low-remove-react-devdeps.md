---
id: 7
section: frontend
status: done
severity: low
---

# Move React Types to devDependencies

`@types/react` and `@types/react-dom` are listed in `dependencies` in `package.json` (lines 48-49). They're only needed for Excalidraw integration at build time and should be `devDependencies`.

## Implementation Plan

### Approach

Manual `package.json` edit + verify build.

### Steps

1. Move `@types/react` and `@types/react-dom` from `dependencies` to `devDependencies` in `package.json` (lines 48-49)
2. Run `npm install` to update lockfile
3. Run `npm run build` to verify Excalidraw integration still compiles

## Acceptance Criteria

- [x] Move `@types/react` and `@types/react-dom` to `devDependencies` in `package.json`
- [x] Verify Excalidraw still builds correctly
