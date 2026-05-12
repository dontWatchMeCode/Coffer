# Features

This is a concise feature map of the app.

## Accounts

- Register, log in, reset passwords, verify email, and confirm passwords.
- Update profile name and email.
- Change password.
- Manage two-factor authentication and recovery codes.
- Change appearance preferences.
- Delete account.

## Teams

- Create and switch teams.
- Personal teams are supported.
- Manage team name and settings.
- Invite members by email.
- Accept or cancel invitations.
- Update member roles or remove members.
- Roles are owner, admin, and member.

## Workspace

- Team dashboard route for the current workspace.
- Sidebar navigation for core team areas.
- Global search shortcut through `Ctrl+K` or `Cmd+K`.
- MCP token page for team-scoped external access.

## Records and Teams

- Most user data is team-scoped.
- Records are created, queried, searched, tagged, and linked inside the current team.
- Team ownership is applied by shared model behavior on configured team models.
- Team-scoped records cannot be saved or queried without an active team context.
- Policies and team membership decide who can view or change records.

## Tasks

- Projects group team tasks, comments, assignment, progress, and due dates.
- Details: [Tasks](pages.md#tasks)

## Calendar

- Calendar manages team events with dates and optional times.
- Details: [Calendar](pages.md#calendar)

## Contacts

- Contacts manage team address book entries and labeled contact methods.
- Details: [Contacts](pages.md#contacts)

## Bookmarks

- Bookmarks track useful team links with descriptions and notes.
- Details: [Bookmarks](pages.md#bookmarks)

## Notes

- Notes capture team knowledge in text or Excalidraw format.
- Details: [Notes](pages.md#notes)

## Log

- Log captures quick team notes and thoughts with an optional category.
- Details: [Log](pages.md#log)

## Collections

- Collections group related team records through shared links.
- Details: [Collections](pages.md#collections)

## Search

- Global search covers configured record types.
- Search supports prefixes for narrowing results.
- Link candidate search uses the same record registry.
- Prefixes: `t` tasks, `c` contacts, `e` events, `p` projects, `b` bookmarks, `n` notes, `s` subscriptions, `g` log entries, `l` collections.

## Tags

- Supported records can have team tags.
- Tags can be selected from existing tags or created while attaching.
- Unused tags are cleaned up after removal.

## Record Links

- Supported records can be linked to each other.
- Links are bidirectional related-record links.
- Linked records show title, type, URL, and preview where available.

## Activity History

- Supported records track meaningful changes.
- Activity can include field diffs, tag changes, and link changes.
- MCP changes can be attributed to the token used.

## MCP

- Teams can create MCP bearer tokens.
- Tokens can expire.
- Tokens can be scoped per record area as none, read, or write.
- Task access can be limited to selected projects.
- The records server exposes schema, search, CRUD, links, and tags tools.

## Adding Record Areas

- New record areas should follow the same team ownership, authorization, search, tags, links, and MCP exposure patterns when they need to behave like existing records.
