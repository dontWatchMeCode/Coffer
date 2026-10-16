---
id: 10
section: design
status: todo
severity: low
---

# Add Branding and Logos

Design and integrate application logos and branding assets.

## Scope

- App logo (used in sidebar, login page, browser tab)
- Favicon
- Any other branding assets (OG images, etc.)

## Acceptance Criteria

- [x] Design app logo (SVG preferred for scalability) — open-desk mark in `resources/js/components/app/AppLogoIcon.vue` (`viewBox 0 0 64 64`, `currentColor`)
- [x] Add favicon — `public/favicon.svg` (geometry matches `AppLogoIcon.vue`) plus `public/favicon.ico` and `public/apple-touch-icon.png`
- [x] Integrate logo into sidebar — shared `AppLogo` / `AppLogoIcon`
- [x] Integrate logo into login/register pages — `AuthSplitLayout.vue`, `AuthSimpleLayout.vue`, `AuthCardLayout.vue`
- [ ] Add Open Graph meta tags with OG image — remaining work, keeps this item actionable
- [x] Ensure branding works in both light and dark mode — page logo inherits page theme via `currentColor`; SVG favicon follows browser `prefers-color-scheme` (`#7043aa` light / `#bd93f9` dark), not page theme; ICO and Apple touch icon use fixed dark tile
