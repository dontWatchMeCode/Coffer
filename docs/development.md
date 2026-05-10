# Development

## Stack

- Laravel backend.
- Inertia and Vue frontend.
- Tailwind CSS styling.
- Wayfinder-generated route helpers.
- Pest tests.
- Pint, PHPStan, ESLint, Prettier, Rector, and Vite checks.

## Code Structure

See [Code structure](structure.md) for where the main app code lives.

## Local Setup

```sh
composer setup
composer dev
```

## Common Checks

```sh
composer test
npm run types:check
npm run build
```

## Full QA

```sh
composer qa
```

## Local Data

```sh
composer setup-dev
```

This refreshes the SQLite database and generates test records.
