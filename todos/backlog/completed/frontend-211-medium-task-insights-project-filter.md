---
id: 211
section: frontend
status: done
severity: medium
---

# Add Project Filter to Task Insights

Allow Task Insights to be filtered by a selected project.

## Requirements

- Add a project selector to the Task Insights page.
- Include an “All projects” option as the default.
- Scope Task Insights charts and range-based distributions to the selected project.
- Keep overall task KPIs consistent with the selected project when a project is chosen.
- Preserve the selected project in the URL query string.

## Acceptance Criteria

- [x] Task Insights has a project select control.
- [x] Selecting a project refreshes Insights data without leaving the page.
- [x] Charts, status breakdown, assignment distribution, and KPIs reflect the selected project.
- [x] Team scoping and feature gating remain enforced.
- [x] Feature tests cover all-project and project-filtered results.
