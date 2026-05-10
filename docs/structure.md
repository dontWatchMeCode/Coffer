# Code Structure

This is a high-level map of where code lives in the app.

## Backend

- `app/Actions`: Fortify authentication actions and team invitation handling.
- `app/Models`: users, teams, team records, tags, links, and MCP tokens.
- `app/Http/Controllers`: page controllers and form/action endpoints.
- `app/Http/Requests`: request validation and request-level authorization.
- `app/Policies`: authorization for teams and records.
- `app/Concerns`: shared model behavior such as team ownership, tags, links, and activity history.
- `app/Services`: record search, MCP record handling, page data, and helper logic.
- `app/Mcp`: MCP server and tools exposed to external clients.
- `app/Enums`: shared values such as team roles, permissions, and task statuses.
- `app/Rules`: custom validation rules.
- `app/Providers`: Laravel service providers and package setup.

## Frontend

- `resources/js/pages`: Inertia page components.
- `resources/js/components`: shared app, page, record, form, auth, and UI components.
- `resources/js/components/ui`: reusable base UI components.
- `resources/js/composables`: shared Vue composables.
- `resources/js/layouts`: app, auth, and settings layouts.
- `resources/js/types`: shared TypeScript types.
- `resources/js/routes` and `resources/js/actions`: generated Wayfinder route/action helpers.
- `resources/js/wayfinder`: generated Wayfinder route and type bundles.
- `resources/js/lib`: frontend utility code.

## Routes

- `routes/web.php`: public, dashboard, team workspace, and record routes.
- `routes/settings.php`: profile, security, appearance, and team settings routes.
- `routes/ai.php`: MCP route registration.
- `routes/console.php`: console routes.

## Data and Tests

- `database/migrations`: database schema changes.
- `database/factories`: model factories for tests and local data.
- `database/seeders`: seeders.
- `tests`: Pest feature, unit, browser, and architecture tests.

## Tooling

- `composer.json`: PHP dependencies and project scripts.
- `package.json`: frontend dependencies and scripts.
- `vite.config.ts`: Vite, Vue, Tailwind, Inertia, and Wayfinder build setup.
- `pint.json`, `phpstan.neon`, `rector.php`, `eslint.config.js`, `.prettierrc`: formatting and analysis configuration.

## Where to Add Things

- New backend page: add a controller in `app/Http/Controllers`, a page in `resources/js/pages`, and a route in `routes/web.php` or `routes/settings.php`.
- New form action: add a request class in `app/Http/Requests`, a controller method, and a route.
- New shared UI: add it under `resources/js/components`; base primitives belong under `resources/js/components/ui`.
- New record type: add the model, migration, policy, requests, pages, routes, search registration, and shared record behavior if it should support tags, links, activity, or MCP.
- New MCP tool: add it under `app/Mcp/Tools` and register it on the MCP server.
- New frontend route usage: use generated Wayfinder helpers instead of hardcoded app URLs.
