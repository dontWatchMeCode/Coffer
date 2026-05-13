---
id: 22
section: backend
status: todo
severity: medium
---

# Add Subscription Categories

Replace free-text subscription categories with a scoped category system similar to record tags, while allowing only one category per subscription.

## Current Implementation Notes

- `subscriptions.category` is currently a nullable text column with an index.
- Create/edit subscription UI currently uses a plain text input for category.
- Record tags use `tags` and `taggables`, are scoped by team, provide candidates, and call `Tag::deleteUnused()` after detach.
- Subscription categories should be scoped to subscriptions only, not shared with record tags.

## Implementation Plan

1. Add a `subscription_categories` table scoped by `team_id` with normalized name/slug and uniqueness per team.
2. Add `subscription_category_id` to `subscriptions`, backfilling from the existing text `category` values.
3. Keep a compatibility accessor/payload field named `category` if existing frontend/search/MCP payloads expect a string.
4. Add create/select behavior for categories in subscription create and edit forms.
5. Automatically delete unused categories after a subscription changes category or is deleted.
6. Update search, MCP validation/payloads, factories, and test record generation to use the selected category name.

## Acceptance Criteria

- [ ] Subscription category is selected from existing categories or created inline
- [ ] Only one category can be selected per subscription
- [ ] Categories are scoped to the current team and subscriptions only
- [ ] Removing a category from the last subscription deletes the unused category
- [ ] Existing free-text categories are migrated without data loss
- [ ] Search still matches subscription category names
- [ ] MCP create/update/read still exposes category behavior clearly
- [ ] Tests cover create, update, cleanup, migration/backfill, search, and MCP payloads
