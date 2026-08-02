# MCP

The app exposes a team-scoped MCP records server for external clients and agents.

## Access

- Endpoint: `/mcp/records`
- Auth: bearer token generated from the team's MCP page.
- Scope: the token's team only.
- Permissions: record areas can be set to `none`, `read`, or `write`.
- Tasks can also be limited to selected projects.
- Tokens can have an optional expiry date.

## Record Types

- `task`
- `calendar_event`
- `contact`
- `bookmark`
- `subscription`
- `note`
- `collection`
- `log_entry`
- `file`

## Tools

- `records.schema`: describe supported records, fields, tags, and links.
- `records.search`: search readable records.
- `records.get`: fetch one readable record.
- `records.create`: create writable records.
- `records.update`: update writable records.
- `records.delete`: delete writable records.
- `records.link`: link two records.
- `records.unlink`: remove a record link.
- `records.related`: list linked records.
- `records.tags.add`: add tags to a record.
- `records.tags.remove`: remove tags from a record.
- `records.tags.list`: list record tags.
- `records.task_comments.list`: list block comments for a task.
- `records.task_comments.add`: add a block comment to a task.

Common record payload fields returned by MCP: `id`, `type`, `title`, `preview`, `url`, `data`, `created_at`, and `updated_at`. `records.get` also includes `tags` and `related` when allowed.

Tool input fields:

- `records.search`: `query`, optional `type`, optional `limit` (1-50).
- `records.get`, `records.delete`, `records.related`, `records.tags.list`: `type`, `id`.
- `records.create`: `type`, `data`.
- `records.update`: `type`, `id`, `data`.
- `records.link`, `records.unlink`: `source_type`, `source_id`, `target_type`, `target_id`.
- `records.tags.add`, `records.tags.remove`: `type`, `id`, `tags`.
- `records.task_comments.list`: `task_id`, optional `limit` (1-100).
- `records.task_comments.add`: `task_id`, `blocks`.

Record data fields:

- `task`: `project_id`, `assigned_to`, `title`, `description`, `status`, `progress`, `time_estimate`, `position`, `due_at`. Create requires `project_id`, `title`, `status`.
- `calendar_event`: `title`, `description`, `date`, `time`. Create requires `title`, `date`.
- `contact`: `name`, `phone_numbers`, `email_addresses`, `links`, `address`, `additional_info`. Create requires `name`.
- `bookmark`: `title`, `url`, `description`, `notes`. Create requires `title`, `url`.
- `subscription`: `name`, `price`, `currency`, `billing_cycle`, `first_billing_date`, `next_billing_date`, `url`, `description`, `notes`, `is_active`, `category`. Create requires `name`; billing cycle is `weekly`, `monthly`, or `yearly`.
- `note`: `title`, `blocks`. Create requires `title`.
- `collection`: `title`, `description`. Create requires `title`.
- `log_entry`: `body`, `category`. Create requires `body`.
- `file`: `title`, `description`, `original_name`, `mime_type`, `size`, `width`, `height`, `content`. Create requires `title`.

## Notes Through MCP

Use `blocks` for notes. Each block has `type`, `position`, and optional `payload`. Supported types are `text`, `excalidraw`, and `mermaid`; text/Mermaid payloads use `content`, Excalidraw payloads use `scene`.

## Task Comments Through MCP

Task comments are separate from task CRUD. Use `records.task_comments.list` and `records.task_comments.add`. Comments use the same `text`, `excalidraw`, and `mermaid` blocks as notes and include author, source, optional MCP token attribution, and timestamps.

## Files Through MCP

Use optional `content` (base64-encoded bytes) to upload a file when creating or updating. Metadata-only creates/updates are supported without `content`. The MCP server never exposes raw file bytes or public storage URLs.

Only JPEG, PNG, GIF, and WebP content up to 100 MB is accepted. Data URI format is accepted. `size` and `mime_type` are auto-populated from decoded bytes when content is provided.

## Permission Model

- Read tools only return record types allowed by the token.
- Write tools require write permission for the target record type.
- Task tools also respect project scope when configured.
- Standard team authorization still applies.
