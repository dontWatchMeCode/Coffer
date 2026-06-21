---
id: 211
section: frontend
status: todo
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

- [ ] Task Insights has a project select control.
- [ ] Selecting a project refreshes Insights data without leaving the page.
- [ ] Charts, status breakdown, assignment distribution, and KPIs reflect the selected project.
- [ ] Team scoping and feature gating remain enforced.
- [ ] Feature tests cover all-project and project-filtered results.
