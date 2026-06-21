---
id: 212
section: backend
status: todo
severity: medium
---

# Add First Billing Date to Subscriptions

Track the first billing date for each subscription.

## Requirements

- Add a nullable `first_billing_date` field to subscriptions.
- Show and edit First Billing Date in subscription create/edit flows.
- Validate the field as an optional date.
- Include the value wherever subscription details are displayed.

## Acceptance Criteria

- [ ] Subscriptions store `first_billing_date`.
- [ ] Create and edit forms include First Billing Date.
- [ ] Validation accepts empty values and valid dates only.
- [ ] Subscription detail views display First Billing Date when present.
- [ ] Tests cover creating and updating the field.
