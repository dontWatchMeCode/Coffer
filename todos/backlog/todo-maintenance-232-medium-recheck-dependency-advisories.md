---
id: 232
section: maintenance
status: todo
severity: medium
---

# Re-check Remaining Dependency Advisories for Upstream Fixes

Twelve npm production advisories were allowlisted in `scripts/npm-audit-gate.mjs` because no compatible upstream fix existed (Excalidraw 0.18.1 is latest; nested mermaid/parser chains unpatched). Re-audit periodically and drop allowlist entries as fixed releases land.

## Acceptance Criteria

- [ ] Run `composer audit` and confirm zero advisories.
- [ ] Run `node scripts/npm-audit-gate.mjs` and review the allowlist against current upstream releases.
- [ ] For each fixed package (Excalidraw > 0.18.1, `@mermaid-js/parser`, chevrotain/langium chain, nanoid, lodash-es, braces, chokidar, sass): update, remove its allowlist entry with justification, and run full `composer qa`.
- [ ] Also review dev-tree advisories (`npm audit` without `--omit=dev`), which the gate does not cover.
