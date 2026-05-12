---
id: 15
section: frontend
status: todo
severity: low
---

# Add MCP Setup Instructions to Token Page

The MCP token info box should include ready-to-copy configuration snippets for common coding agents and MCP clients.

## Scope

- Display setup instructions alongside the token info
- Provide copy-paste JSON config for popular MCP clients (Claude Desktop, Cursor, Windsurf, Cline, etc.)
- Include the token URL and bearer token in the snippets

## Implementation Plan

### Approach

Add collapsible section to the existing MCP token management page with copy-paste config snippets.

### Current State

- MCP token UI lives at `{current_team}/mcp` routes (`routes/web.php:46-49`)
- Existing token display and CRUD management already functional

### Steps

1. Create config snippet templates for each client:
   - **Claude Desktop**: `claude_desktop_config.json` format — `{"mcpServers":{"dashboard":{"url":"...","headers":{"Authorization":"Bearer ..."}}}}`
   - **Cursor**: `.cursor/mcp.json` format — similar structure
   - **Windsurf**: config format — similar structure
   - **Cline**: VS Code settings format — `cline.mcpServers` in settings.json
2. Add collapsible section (using existing `Collapsible` component) to MCP token page:
   - "Setup Instructions" heading
   - Tabbed or accordion layout per client
   - Each tab shows the JSON config with the team's MCP endpoint URL (`route('mcp.records')`) and bearer token pre-filled
   - One-click copy button per snippet (using `navigator.clipboard`)
3. Auto-populate URL from `route('mcp.records')` and token from the displayed token value
4. Keep existing token display and management UI unchanged

### Dependencies

- Can add API-specific snippets after todo-8 (REST API) is done

## Acceptance Criteria

- [ ] Add collapsible setup instructions section to the MCP token page
- [ ] Include config snippets for at least: Claude Desktop, Cursor, Windsurf, Cline
- [ ] Each snippet should have a one-click copy button
- [ ] Snippets should auto-populate with the team's MCP endpoint URL and token
- [ ] Keep the existing token display and management UI
