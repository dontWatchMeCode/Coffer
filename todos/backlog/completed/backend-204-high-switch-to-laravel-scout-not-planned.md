---
id: 204
section: backend
status: done
severity: high
---

# Switch to Laravel Scout (Not Planned)

This is not currently planned. The project reverted the Scout implementation and kept SQL-backed record search with escaped `LIKE` filters.

Current search uses `Filterable` trait and inline `LIKE '%query%'` queries across the codebase. Replace with Laravel Scout for proper full-text search.

## Current Setup

- **`app/Concerns/Filterable.php`** — `scopeSearch()` builds `WHERE column LIKE '%search%'` with `addcslashes` escaping
- **`app/Concerns/EscapesLikeWildcards.php`** — utility trait used by `RecordSearchService`, `McpRecordService`, `SubscriptionCategoryController`, `RecordTagController` — parallel duplication of LIKE building
- **`app/Services/RecordSearchService.php`** — `baseSearchQuery()` has its own independent LIKE implementation that doesn't use `Filterable::scopeSearch`; builds `WHERE column LIKE '%...%'` inline using `RecordSearchRegistry` column definitions
- **`app/Mcp/Tools/SearchRecordsTool.php`** — delegates to `McpRecordService::search()`
- Every controller uses `->search($q, [...columns])` — Calendar, Tasks, Subscriptions, RecordCollections, Notes, Contacts, Log, Bookmarks, SearchPageController
- No indexing, no stemming, no relevance ranking

## Why Scout

- Full-text search via MySQL `MATCH … AGAINST` or external driver (MeiliSearch/Algolia)
- Automatic model indexing via `Searchable` trait
- Relevance-ranked results (database driver returns results in relevance order; external drivers provide true relevance features)
- Cleaner API: `Model::search('query')->get()`
- Better performance on large datasets (full-text indexes vs `LIKE %...%`)

## Steps

- [ ] Install `laravel/scout`
- [ ] Pick driver: database (MySQL full-text, no extra infra) or MeiliSearch (true relevance, no stopword/min-length issues)
- [ ] Add `Searchable` trait to all searchable models
- [ ] Define `toSearchableArray()` on each model, serializing JSON/searchable fields appropriately
- [ ] Define full-text indexes in migrations (after `toSearchableArray` determines which columns need indexing; note: JSON columns like contacts' `phone_numbers`, `email_addresses`, `links` need serialization in `toSearchableArray`)
- [ ] Configure `SCOUT_QUEUE=true` for async indexing on large tables
- [ ] Run `php artisan scout:import` to index existing records
- [ ] Refactor `RecordSearchService::baseSearchQuery()` to use Scout instead of inline LIKE
- [ ] Replace `Filterable::scopeSearch` usage with Scout's `search()` API across all controllers
- [ ] Replace `EscapesLikeWildcards` usage in `McpRecordService`, `SubscriptionCategoryController`, `RecordTagController` with Scout
- [ ] Decide `RecordSearchRegistry` fate — retire, adapt, or keep alongside Scout
- [ ] Remove `Filterable` and `EscapesLikeWildcards` traits if no longer needed
- [ ] Write new tests for Scout search behavior (no existing tests cover `scopeSearch`)
- [ ] Update `RecordSearchRegistry` docs/registration entries
- [ ] Remove `Filterable.php` and `EscapesLikeWildcards.php` references in todos/docs

## Models to Make Searchable

- Task
- Subscription
- RecordCollection (not "Collection")
- Note
- Contact
- CalendarEvent
- LogEntry
- Bookmark
- Project (already in `RecordSearchRegistry`; needs `Searchable` too if Scout replaces global search)

## Controllers Using Search (all need updates)

- CalendarPageController
- TaskPageController
- SubscriptionPageController
- CollectionPageController
- NotePageController
- ContactPageController
- LogPageController
- BookmarkPageController
- SearchPageController
- SearchController (injects RecordSearchService)
- RecordLinkController (injects RecordSearchService)

## Other Services/Controllers Using LIKE Search

- RecordSearchService (inline LIKE in `baseSearchQuery`)
- McpRecordService (uses `EscapesLikeWildcards`)
- SubscriptionCategoryController (uses `EscapesLikeWildcards`)
- RecordTagController (uses `EscapesLikeWildcards`)

## Gotchas & Caveats

- **Transition strategy**: LIKE search and MySQL full-text search can return different result sets (different tokenization, stopword filtering, minimum word length). Controllers can't be swapped piecemeal without accepting result-set changes. Consider a single cutover or feature-flag approach.
- **MySQL `ft_min_word_len`**: Default is 4, meaning 1–3 character terms silently return zero results with the database driver. This is a known surprise when switching from `LIKE`.
- **Stopwords**: MySQL full-text has a default stopword list — common words like "the", "and" are ignored. This can cause seemingly valid queries to return fewer results.
- **JSON columns**: Contacts' `phone_numbers`, `email_addresses`, `links` are JSON — MySQL cannot create full-text indexes on JSON columns. Must be serialized to text in `toSearchableArray()`.
- **Soft deletes**: Scout's `Searchable` respects `SoftDeletes`. Verify each model uses `SoftDeletes` and test the interaction.
- **`RecordSearchService`** is the biggest refactor — it has its own LIKE implementation independent of `Filterable`.
- **No existing tests** cover `scopeSearch` — all search tests will be new.
- **`RecordSearchRegistry`** centrally defines searchable columns — may be partially or fully replaced by `toSearchableArray()`.
