---
id: 9
section: frontend
status: todo
severity: medium
---

# Add Record-Specific Dashboards

Add dashboard views for records as an alternative to list/card views, with charts and analytics.

## Examples

- Subscriptions: monthly spend, spending trend over months, category breakdown
- Tasks: completion rate, overdue count, assignment distribution
- Calendar: events per month, upcoming events summary

## Implementation Plan

### Approach

Use Unovis via shadcn-vue Chart component (`npx shadcn-vue@latest add chart`). Project already uses shadcn-vue with reka-ui.

### Backend

1. Create `app/Http/Controllers/DashboardController.php`:
   - `GET /{current_team}/dashboard/subscriptions` — aggregate query: monthly spend grouped by month, category breakdown, active vs inactive counts
   - `GET /{current_team}/dashboard/tasks` — aggregate query: completion rate, overdue count, per-status counts, assignment distribution
   - `GET /{current_team}/dashboard/calendar` — aggregate query: events per month, upcoming events (next 7/30 days)
   - All scoped to `currentTeam` via `whereBelongsTo()`
   - Accept `?range=` query param (this_month, last_3_months, this_year)

### Frontend

2. Install chart component: `npx shadcn-vue@latest add chart` (adds `@unovis/vue` dependency)
3. Create reusable components in `resources/js/components/dashboard/`:
   - `DashboardCard.vue` — card container with title + optional time range selector
   - `TimeRangeSelect.vue` — dropdown (this month, last 3 months, this year) using existing `Select` component
   - `KpiCard.vue` — single metric display (label, value, trend indicator)
4. Build dashboard pages:
   - `resources/js/pages/subscriptions/Dashboard.vue`:
     - Area chart (spend trend over time using `VisArea`)
     - Donut/pie chart (category breakdown)
     - KPI cards (total monthly spend, active subscriptions, upcoming renewals)
   - `resources/js/pages/tasks/Dashboard.vue`:
     - Bar chart (tasks per status: todo, in_progress, done)
     - KPI cards (completion rate %, overdue count, total open)
   - `resources/js/pages/calendar/Dashboard.vue`:
     - Bar chart (events per month)
     - List of upcoming events
5. Add sidebar navigation entries per record area (under bookmarks, tasks, calendar, etc.)

### Tests

6. Feature tests for each dashboard endpoint — verify correct aggregation, team scoping, time range filtering

### Dependencies

- Unblocks: todo-12 (customizable dashboard — can reuse chart components)

## Acceptance Criteria

- [ ] Choose charting library (Unovis via shadcn-vue Chart)
- [ ] Create reusable dashboard card/chart components
- [ ] Add subscription dashboard (monthly spend, trend, category breakdown)
- [ ] Add task dashboard (completion rate, overdue, assignment)
- [ ] Add calendar dashboard (events per period, upcoming)
- [ ] Support time range selection (this month, last 3 months, this year)
- [ ] Wire dashboards into sidebar navigation per record area
- [ ] Ensure dashboards are responsive
