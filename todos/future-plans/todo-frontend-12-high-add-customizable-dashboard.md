---
id: 12
section: frontend
status: todo
severity: high
---

# Add Customizable Team Dashboard

Add a build-your-own dashboard similar to [Homarr](https://homarr.dev/) where teams can compose their workspace from configurable widgets.

## Scope

- Widget-based dashboard as the main team home page
- Draggable/resizable widget layout
- Widgets for: quick actions, recent records, task summary, upcoming events, bookmarks, notes, subscriptions overview, custom links, etc.
- Persist layout per team (or per user within team)
- Lightweight widget system that can be extended

## Acceptance Criteria

- [ ] Design widget system architecture (registration, rendering, config)
- [ ] Implement drag-and-drop grid layout (e.g. vue-grid-layout or similar)
- [ ] Create core widgets (recent tasks, upcoming events, quick links, bookmarks, team stats)
- [ ] Add widget configuration (size, position, settings per widget type)
- [ ] Persist dashboard layout to database
- [ ] Add widget catalog / add-widget UI
- [ ] Ensure responsive behavior (stack on mobile)
- [ ] Wire as the default team home page
