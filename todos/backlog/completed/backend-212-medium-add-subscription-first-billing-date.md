---
id: 212
section: backend
status: done
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

- [x] Subscriptions store `first_billing_date`.
- [x] Create and edit forms include First Billing Date.
- [x] Validation accepts empty values and valid dates only.
- [x] Subscription detail views display First Billing Date when present.
- [x] Tests cover creating and updating the field.
