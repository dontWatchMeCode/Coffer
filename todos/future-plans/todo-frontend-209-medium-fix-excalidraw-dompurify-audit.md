---
id: 209
section: frontend
status: todo
severity: medium
---

# Fix Excalidraw And DOMPurify Audit Findings

Resolve the remaining npm audit findings that were intentionally deferred after applying non-breaking fixes with the two-week package age policy.

## Context

- `dompurify` remains flagged because the fixed release is newer than the current `min-release-age=14` policy allows.
- `@excalidraw/excalidraw` remains flagged through nested `@excalidraw/mermaid-to-excalidraw`, `nanoid`, `@mermaid-js/parser`, `langium`, `chevrotain`, and `lodash-es` dependencies.
- `npm audit fix --force` currently proposes a breaking Excalidraw change, so this needs compatibility review before applying.

## Recommended Work

1. Re-run `npm audit` after the DOMPurify fixed version is at least 14 days old.
2. Upgrade `dompurify` to the first safe version allowed by `min-release-age=14`.
3. Review current Excalidraw releases and changelogs for a non-breaking or acceptable breaking upgrade path.
4. Smoke test drawing creation, drawing editing, readonly drawing previews, and activity history drawing previews.
5. Re-run frontend checks and audit.

## Acceptance Criteria

- [ ] `dompurify` audit findings are resolved without bypassing the two-week package age policy
- [ ] Excalidraw-related audit findings are resolved or explicitly documented as accepted risk
- [ ] Drawing editor still loads and saves scenes
- [ ] Readonly Excalidraw previews still render
- [ ] `npm run lint:check` passes
- [ ] `npm run types:check` passes
- [ ] `npm run test` passes
- [ ] `npm audit` has no remaining unreviewed frontend runtime findings
