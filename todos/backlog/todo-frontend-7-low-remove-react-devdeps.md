---
id: 7
section: frontend
status: todo
severity: low
---

# Move React Types to devDependencies

`@types/react` and `@types/react-dom` are listed in `dependencies` in `package.json` (lines 48-49). They're only needed for Excalidraw integration at build time and should be `devDependencies`.

## Acceptance Criteria

- [ ] Move `@types/react` and `@types/react-dom` to `devDependencies` in `package.json`
- [ ] Verify Excalidraw still builds correctly
