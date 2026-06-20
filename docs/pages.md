# Main Pages

The main team pages are Tasks, Calendar, Contacts, Bookmarks, Notes, Log, and Collections.

## Shared Record Tools

Supported records can be tagged and linked to other records. Tags help group records by topic, while links connect related work, knowledge, contacts, events, bookmarks, notes, and collections across the team.

Deleted records move to the trash page for their module. Trash pages let team members review, restore, or permanently delete deleted records.

## Tasks

Tasks are the team work planning area.

Pages:

- Project list: choose a project, review project counts, and create projects.
- Project detail: review a project, create tasks, and update work status.
- Task trash: view, restore, or permanently delete deleted tasks for a project.
- Task edit: edit task details and related records.

Features:

- Projects group tasks and can be archived.
- Tasks support title, description, status, progress, assignee, creator, position, and due date.
- Tasks can be assigned to team members.
- Tasks have comments that can be created, edited, and deleted.
- Task and project changes can appear in activity history.
- Tasks and projects can use shared tags and related-record links.

Search and MCP:

- Global search includes tasks and projects.
- Task search uses the `t` prefix; project search uses the `p` prefix.
- MCP tokens can allow task read/write access and optionally limit tasks to selected projects.

## Calendar

Calendar is the team event area.

Pages:

- Calendar list/grid: view and create team events.
- Calendar trash: view, restore, or permanently delete deleted events.
- Event edit: update event details.

Features:

- Events support title, description, date, and optional time.
- Events can be created, edited, and deleted.
- Events belong to the active team.
- Events can use shared tags and related-record links.
- Event changes can appear in activity history.

Search and MCP:

- Global search includes calendar events.
- Event search uses the `e` prefix.
- MCP tokens can allow calendar read/write access.

## Contacts

Contacts are the team address book.

Pages:

- Contact list: view and create contacts.
- Contact trash: view, restore, or permanently delete deleted contacts.
- Contact detail: review and edit contact details and related records.

Features:

- Contacts support name, phone numbers, email addresses, links, address, and additional info.
- Phone numbers, email addresses, and links can have labels.
- Empty contact entries are ignored when saving.
- Contacts can use shared tags and related-record links.
- Contact changes can appear in activity history.

Search and MCP:

- Global search includes contacts.
- Contact search uses the `c` prefix.
- MCP tokens can allow contact read/write access.

## Bookmarks

Bookmarks track useful team links.

Pages:

- Bookmark list: view and create bookmarks.
- Bookmark trash: view, restore, or permanently delete deleted bookmarks.
- Bookmark detail: review and edit bookmark details and related records.

Features:

- Bookmarks support title, URL, description, and notes.
- Bookmarks belong to the active team.
- Bookmarks can use shared tags and related-record links.
- Bookmark changes can appear in activity history.

Search and MCP:

- Global search includes bookmarks.
- Bookmark search uses the `b` prefix.
- MCP tokens can allow bookmark read/write access.

## Notes

Notes capture team knowledge.

Pages:

- Note list: view and create notes.
- Note trash: view, restore, or permanently delete deleted notes.
- Note detail: edit note content and related records.

Features:

- Notes support `text` and `excalidraw` formats.
- Text notes are Markdown-backed.
- Excalidraw notes store drawing data.
- A note uses one format at a time.
- Notes can use shared tags and related-record links.
- Note changes can appear in activity history.

Search and MCP:

- Global search includes notes.
- Note search uses the `n` prefix.
- MCP tokens can allow note read/write access.
- Through MCP, use `format: "text"` or `format: "excalidraw"`.

## Log

Log is the team quick-notes area.

Pages:

- Log page: view, create, edit, and delete log entries.
- Log trash: view, restore, or permanently delete deleted log entries.

Features:

- Log entries support body text and an optional category.
- Entries are ordered newest first.
- Entries belong to the active team.
- The Log page includes right-side search and multi-category filtering.
- Log entry body and category changes are available through activity history.
- Log entries do not support tags or related-record links.

Search and MCP:

- Global search includes log entries (backend; frontend rendering not yet wired).
- Log entry search uses the `g` prefix.
- MCP tokens can allow log entry read/write access.

## Collections

Collections group related team records.

Pages:

- Collection list: view and create collections.
- Collection trash: view, restore, or permanently delete deleted collections.
- Collection detail: review and maintain linked records.

Features:

- Collections support title and description.
- Collections belong to the active team.
- Collections can use shared tags.
- Collections use related-record links to group records.
- Link and tag changes on collections can appear in activity history.

Search and MCP:

- Global search includes collections.
- Collection search uses the `l` prefix.
- MCP tokens can allow collection read/write access.
