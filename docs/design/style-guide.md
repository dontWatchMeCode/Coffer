# Dashboard style guide — Pastel Dracula

Visual reference: [example.html](./example.html).

This guide extracts the reference's visual language for reuse across the dashboard. Values below reflect the reference unless explicitly labeled **Production addition**. The HTML is a visual mockup, not a complete interaction specification.

## 1. Design direction

A compact, terminal-inspired workspace: dark blue-gray surfaces, monospace typography, lavender highlights, square corners, and visible structural borders.

- Prioritize scanability and information density.
- Establish hierarchy through surface color, borders, type size, and spacing.
- Use lavender for selection and emphasis; green and red communicate outcomes.
- Keep controls and panels flat. No shadows, rounded cards, glass effects, or decorative gradients.
- Reserve gradients for chart areas and progress fills.
- Use short, concrete labels and quiet metadata. Uppercase is selective: status labels, compact action labels, and date chips.

## 2. Color tokens

Preserve these semantic roles rather than choosing colors independently for each component.

| Token | Value | Role |
| --- | --- | --- |
| `--bg` | `#282a36` | Page background; text on accent fills |
| `--panel` | `#2e3040` | Panels, banners, and controls |
| `--panel2` | `#353849` | Panel headers, date chips, progress tracks |
| `--accent` | `#bd93f9` | Active navigation, emphasis, open status, chart series |
| `--dim` | `#808cb8` | Secondary text, metadata, chart labels |
| `--green` | `#8ff0a4` | Positive changes and completed states |
| `--red` | `#ff9a9a` | Overdue and negative states |
| `--line` | `#44475a` | Borders, separators, chart gridlines |
| `--txt` | `#d6d8e8` | Default text |
| `--heading` | `#f8f8f2` | Headings, key values, emphasized labels |

The progress gradient starts at `#9d7bee` and ends at `--accent`. The chart area uses `--accent`, fading from 38% opacity to transparent.

### Color usage

- Background → panel → panel header creates three restrained surface levels.
- Selected navigation uses a solid accent background with `--bg` text.
- Panel titles use accent text; page headings use heading text.
- Status always includes a readable label, such as `OVERDUE` or `DONE`.
- Keep red and green semantic rather than decorative.

## 3. Typography

Use **IBM Plex Mono**, with `monospace` fallback, throughout—including buttons and chart labels. Load weights 400, 500, 600, and 700.

| Purpose | Size | Weight / treatment |
| --- | --- | --- |
| Default text and navigation | 13px | 400; line-height 1.5 |
| Page/banner heading | 15px | 600; heading color |
| KPI value | 38px | 700; letter-spacing -1px |
| KPI unit | 14px | Muted; inherits value weight |
| Controls and compact content | 12px | Normal unless emphasized |
| Panel headers, metadata, statuses | 11px | Normal; panel title 700 |
| Tags, counts, axis labels | 10px | Tags/counts 700 |
| Record-type badge | 9px | 700 |

- Keep headings modest: numerical values provide the strongest visual emphasis.
- Distinguish primary text from metadata through color and size.
- Use uppercase action text with `0.06em` tracking where appropriate, as in the MCP button.
- Completed task titles are muted and struck through; their status remains explicit.

**Production addition:** These are reference sizes, not permission to shrink important information indiscriminately. Use relative units where practical, support browser zoom, and enlarge small text when readability requires it.

## 4. Layout and spacing

### Page shell

| Property | Reference value |
| --- | --- |
| Maximum page width | 1400px, centered |
| Page padding | 0 18px 40px |
| Top bar padding | 12px 0 |
| Top bar positioning | Sticky, top 0, z-index 50 |
| Sidebar width | 230px |
| Sidebar/content gap | 26px |
| Layout top margin | 20px |
| Sidebar right padding | 20px |
| Content grid | 12 equal columns |
| Grid gap | 16px |

The sidebar has a dashed right border. The top bar has a solid bottom border and an opaque page-colored background. Main content uses `min-width: 0` so grid children can shrink.

**Production addition:** The workspace header (`AppSidebarHeader`) is sticky (`top-0`, `z-30`), `60px` tall, and contains the mobile-only sidebar toggle (`min-[1081px]:hidden` hamburger), the team switcher, and breadcrumbs. On desktop (`min-[1081px]`) the sidebar is a viewport-fixed `230px` column synced to the shell max-width/gutters with a dashed divider, and its footer sits directly below the nav with a dashed top divider.

### Desktop panel spans

| Panel | Columns |
| --- | --- |
| KPI | 3 each; four across |
| Priority tasks | 7 |
| Spend chart | 5 |
| Progress, calendar, recent records | 4 each; three across |

### Spacing vocabulary

The reference uses purpose-specific values rather than a strict spacing scale:

- **2–4px:** tag padding, tiny label spacing.
- **6–8px:** compact controls, navigation, list rows.
- **10–12px:** internal gaps, horizontal panel padding.
- **14–16px:** panel body padding and grid separation.
- **18–20px:** page gutters, banners, section separation.
- **26px:** sidebar/content separation.
- **40px:** page bottom breathing room.

Reuse these values by role; avoid introducing arbitrary spacing per screen.

### Responsive behavior

At viewport widths **1080px and below**:

- Switch the shell to one column.
- Hide the sidebar behind the header toggle: it opens as a fixed overlay drawer (`280px`, max `85vw`) with dialog semantics (`role="dialog"`, `aria-modal="true"`, labeled site navigation), focus trap with focus restore, Escape to close, clickable backdrop, and body scroll-lock.
- Make every dashboard panel stack full-width in the single column.

**Production addition:** Long titles, identifiers, progress labels, and toolbar content must wrap or reflow without horizontal page overflow. Test narrow screens and zoom; the reference does not define row-level mobile adaptations. Keep essential status text visible.

## 5. Borders and surfaces

- Use square corners throughout: border radius 0.
- Standard border: 1px solid `--line`.
- Dashed borders separate repeated rows and sidebar sections.
- Solid borders define component boundaries and panel headers.
- Remove the final separator in a repeated list.
- Avoid shadows; use surface contrast and borders for structure.

## 6. Component recipes

### Top bar controls

- Team selector: panel background, standard border, 12px text, padding `6px 12px 6px 8px`, 10px internal gap.
- Team name: heading color, weight 600; caret: muted, 10px.
- Utility button: panel background, standard border, padding `6px 10px`, 8px internal gap.
- Count badge: accent fill, background-colored text, 10px bold, 16px height, at least 16px width.
- Settings button: 30px square, centered icon, accessible name.
- Hover: accent border; settings icon also becomes accent-colored.

### Sidebar navigation

- Section label: 11px muted text, 14px bottom spacing.
- Links: 13px text, `4px 0` padding, count aligned right.
- Hover: heading text color, 120ms transition.
- Active link: accent fill, background-colored text and count, `4px 8px` padding, -8px left margin.
- Account metadata: 11px muted text, line-height 1.7, dashed top divider.

### Information banner

- Panel surface, standard border, padding `16px 18px`, 20px bottom margin.
- Small accent tag attached to the top-left border; background-colored 10px bold text, padding `2px 8px`.
- Heading: 15px/600, 14px top margin to clear the tag.
- Supporting summary: 12px muted text, 4px top margin.

### Standard panel

- Outer shell: panel surface, standard border, no outer padding.
- Header: raised panel surface, solid bottom border, padding `7px 12px`.
- Header content: title left, metadata right; 11px text.
- Title: bold accent text. Metadata: muted text.
- Body: padding `14px 12px`.

### KPI panel

- Use the standard panel shell.
- Value: 38px bold heading text with a smaller, muted unit.
- Supporting line: 11px muted text, 6px top margin.
- Highlight only the meaningful positive or negative fragment in green or red.

### Task row

- Three columns: 34px identifier, flexible title, content-sized status.
- Gap: 10px. Vertical padding: 8px. Dashed bottom separator.
- Identifier: 11px muted. Title: default text.
- Project metadata: 11px muted, 6px left margin.
- Status: 11px; accent for open/due, red for overdue, green for done.
- Completed title: muted with strikethrough.

### Progress row

- Horizontal arrangement: label, flexible track, percentage; 10px gaps.
- Label width: 120px in the desktop reference.
- Track: 8px high, standard border, raised panel background.
- Fill: left-to-right lavender gradient; square ends.
- Percentage: 11px muted text, right-aligned in 36px.
- Row spacing: 13px; no trailing margin.

### Calendar row

- Two columns: 56px date chip and flexible event text; 12px gap.
- Row padding: 8px vertically; dashed separator.
- Date chip: raised panel surface, standard border, centered 11px heading text, 3px vertical padding.
- Event title: 12px. Time range: separate line, 11px muted.

### Recent-record row

- Columns: content-sized type badge, flexible title, content-sized type label.
- Gap: 10px. Vertical padding: 7px. Dashed separator.
- Badge: muted fill, background-colored 9px bold text, padding `1px 5px`.
- Record title: 12px. Type label: 10px muted.

## 7. Charts

- Use accent-colored series on the panel surface.
- Line width: 2.5 SVG units; rounded line caps and joins.
- Point markers: page-colored fill with a 2.5-unit accent outline; radius 3.5, final point 4.5 in the reference.
- Area fill: accent at 38% opacity fading to transparent.
- Gridlines: 1-unit line color, quiet and horizontal.
- Labels: muted monospace; 12 SVG units on the vertical axis and 10px for HTML month labels.
- Category totals: two-column label/value list beneath a solid divider, 12px text, values aligned right.

**Production addition:** Scale coordinates and labels from actual data; the reference chart is illustrative. Give charts an accessible name and a textual summary or data table. Do not rely on the plotted line alone to communicate values.

## 8. Interaction and accessibility

**Production additions — not implemented by the reference:**

- Use real links for navigation and buttons for actions. Mark the current navigation link with `aria-current="page"`.
- Provide visible keyboard focus: a 2px accent outline with a 2px offset. Ensure sticky content does not obscure focused controls.
- Give icon-only controls accessible names. Keep keyboard behavior consistent with the control type.
- Preserve compact visuals while providing usable hit areas; meet the 24×24 CSS-pixel target-size minimum or its spacing exceptions, with larger touch targets where practical.
- Check text contrast on each actual surface: target 4.5:1 for normal text and 3:1 for large text. Muted text needs particular attention on raised surfaces; adjust its foreground when necessary.
- Check meaningful control boundaries and state indicators for 3:1 non-text contrast. The decorative line token is not sufficient for every interactive boundary.
- Pair every status color with text or another visible cue.
- Use semantic headings and lists; expose progress with a labeled native progress element or equivalent accessible semantics.
- Keep transitions short and limited to relevant properties. Respect reduced-motion preferences when adding animation.
- Neutralize yellow browser autofill so filled inputs match the theme: an unlayered `-webkit-autofill` override (input, textarea, select) uses foreground text with an inset card-surface cover while keeping the global focus-visible outline.

Forms, dialogs, loading, empty, error, and disabled states are not shown in the reference. When added, reuse this guide's typography, surfaces, borders, and semantic colors; define their behavior explicitly rather than treating the mockup as a complete specification.

## 9. Review checklist

- [ ] IBM Plex Mono throughout, including controls and charts.
- [ ] Shared color roles; no one-off palette drift.
- [ ] Square, flat panels with compact header strips.
- [ ] Solid outer borders and dashed repeated-row separators.
- [ ] Lavender selection; red/green reserved for meaning.
- [ ] Compact typography with clear value/title/metadata hierarchy.
- [ ] Desktop grid and single-column stacking match the reference; below 1081px the sidebar uses the production overlay drawer.
- [ ] Long content and zoom do not break the layout.
- [ ] Keyboard focus, contrast, labels, and hit areas verified.
- [ ] Real data remains readable without relying on color or charts alone.
