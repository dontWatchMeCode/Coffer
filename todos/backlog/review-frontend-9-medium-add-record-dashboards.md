---
id: 9
section: frontend
status: review
severity: medium
---

# Add Record-Specific Insights

Add insights views for records as an alternative to list/card views, with charts and analytics.

## Examples

- Subscriptions: monthly spend, spending trend over months, category breakdown
- Tasks: completion rate, overdue count, assignment distribution
- Calendar: events per month, upcoming events summary

## Implementation Plan

### Approach

Use Unovis via shadcn-vue Chart component (`npx shadcn-vue@latest add chart`). Project already uses shadcn-vue with reka-ui.

### Backend

1. Create record-specific insights controllers:
   - `GET /{current_team}/subscriptions/insights` — aggregate query: monthly spend grouped by month, category breakdown, active vs inactive counts
   - `GET /{current_team}/tasks/insights` — aggregate query: completion rate, overdue count, per-status counts, assignment distribution
   - `GET /{current_team}/calendar/insights` — aggregate query: events per month, upcoming events (next 7/30 days)
   - All scoped to `currentTeam` via `whereBelongsTo()`
   - Accept `?range=` query param (this_month, last_3_months, this_year)

### Frontend

2. Install chart component: `npx shadcn-vue@latest add chart` (adds `@unovis/vue` dependency)
3. Create reusable components in `resources/js/components/dashboard/`:
   - `DashboardCard.vue` — card container with title + optional time range selector
   - `TimeRangeSelect.vue` — dropdown (this month, last 3 months, this year) using existing `Select` component
   - `KpiCard.vue` — single metric display (label, value, trend indicator)
4. Build insights pages:
   - `resources/js/pages/subscriptions/Insights.vue`:
     - Area chart (spend trend over time using `VisArea`)
     - Donut/pie chart (category breakdown)
     - KPI cards (total monthly spend, active subscriptions, upcoming renewals)
   - `resources/js/pages/tasks/Insights.vue`:
     - Bar chart (tasks per status: todo, in_progress, done)
     - KPI cards (completion rate %, overdue count, total open)
   - `resources/js/pages/calendar/Insights.vue`:
     - Bar chart (events per month)
     - List of upcoming events
5. Add separate right-side Insights buttons in each record area toolbar.

### Tests

6. Feature tests for each insights endpoint — verify correct aggregation, team scoping, time range filtering

### Dependencies

- Unblocks: todo-12 (customizable dashboard — can reuse chart components)

## Acceptance Criteria

- [x] Choose charting library (Unovis via shadcn-vue Chart)
- [x] Create reusable insights card/chart components
- [x] Add subscription insights (monthly spend, trend, category breakdown)
- [x] Add task insights (completion rate, overdue, assignment)
- [x] Add calendar insights (events per period, upcoming)
- [x] Support time range selection (this month, last 3 months, this year)
- [x] Wire Insights as standalone toolbar buttons per record area
- [x] Ensure dashboards are responsive
