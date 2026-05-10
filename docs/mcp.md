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
- `note`
- `collection`

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

## Notes Through MCP

Use `format: "text"` for Markdown notes and `format: "excalidraw"` for drawing notes.

## Permission Model

- Read tools only return record types allowed by the token.
- Write tools require write permission for the target record type.
- Task tools also respect project scope when configured.
- Standard team authorization still applies.
