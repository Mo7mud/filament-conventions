# Filament Conventions

The shared Filament rules of the team, delivered as Laravel Boost guidelines, and the shared code that applies them.

- `resources/boost/guidelines/`: the rules. Laravel Boost merges them into `CLAUDE.md`, `AGENTS.md` and the files of the other agents.
- `src/`: shared code, such as `Concerns\HasInstantRelationManagerTabs` for client-side relation manager tabs and `Notifications\DatabaseNotificationsDropdown` for the notification bell as a dropdown.

## Add to a project

1. Add the repository and the package to `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/Mo7mud/filament-conventions" }
],
```

```bash
composer require mo7mud/filament-conventions:^1.0
```

2. Add `"mo7mud/filament-conventions"` to the `packages` list in `boost.json`.
3. Run `php artisan boost:update`.

## Change a rule

Edit the files here, push and tag a new version (like `v1.1.0`), then in every project run `composer update mo7mud/filament-conventions` and `php artisan boost:update`.

## Public repository

This repository is public, so `composer install` works anywhere without a token. Never put secrets, customer data or anything project-specific here; keys belong in each project's `.env`.
