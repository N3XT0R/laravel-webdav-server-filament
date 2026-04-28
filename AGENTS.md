# AGENTS.md

Guidance for AI agents working in this repository.

## What This Is

A Laravel Filament plugin package (`n3xt0r/laravel-webdav-server-filament`) that integrates a
WebDAV server with the Filament admin panel. It is currently in early/skeleton state — the main
class, command, and plugin are stubs awaiting implementation. Requires PHP 8.4+, Laravel 12+/13+,
Filament 5+.

## Docker Execution Requirement

**All PHP and Composer commands must be run inside the `php` Docker container.**

The project runs in a Docker environment defined by `docker-compose.yml`. There is a single service
named `php`. Every command that involves PHP, Composer, PHPUnit, Pint, Testbench, or Artisan must
be prefixed with `docker compose exec php`.

```bash
# Start the container if it is not running
docker compose up -d

# Prefix for every PHP/Composer command
docker compose exec php <command>
```

Do not run PHP or Composer commands directly on the host. They will either fail or produce results
that differ from the container environment.

JavaScript commands (`npm install`, `npm run build`, `npm run dev`) run on the host — there is no
Node service in the Docker setup.

## Commands

### PHP / Composer (always via `docker compose exec php`)

```bash
docker compose exec php composer install          # install dependencies
docker compose exec php composer test             # run all tests with PHPUnit
docker compose exec php composer lint             # auto-fix code style with Pint
docker compose exec php composer test:lint        # check code style without fixing
docker compose exec php composer build            # build workbench assets via Testbench
docker compose exec php composer serve            # build and serve the workbench app

# Run a single test file
docker compose exec php vendor/bin/phpunit tests/ExampleTest.php

# Run tests by name filter
docker compose exec php vendor/bin/phpunit --filter "test name"
```

### JavaScript (on the host)

```bash
npm install
npm run build   # compile JS via esbuild (production)
npm run dev     # compile JS in watch mode
```

## Architecture

### Plugin Entry Points

| File | Role |
|------|------|
| `src/LaravelWebdavServerFilamentPlugin.php` | Implements `Filament\Contracts\Plugin` — registered per-panel via `->plugin(LaravelWebdavServerFilamentPlugin::make())` |
| `src/LaravelWebdavServerFilamentServiceProvider.php` | Extends `Spatie\LaravelPackageTools\PackageServiceProvider`; boots Filament asset/icon registration and stubs publishing |
| `src/LaravelWebdavServerFilament.php` | Main class, currently a stub |
| `src/Facades/LaravelWebdavServerFilament.php` | Laravel facade pointing to the main class |
| `src/Commands/LaravelWebdavServerFilamentCommand.php` | Artisan command stub (`laravel-webdav-server-filament`) |
| `src/Testing/TestsLaravelWebdavServerFilament.php` | Mixed into Livewire's `Testable` for custom test helpers |

### Workbench

`workbench/` is a self-contained Laravel application used as the host environment during testing.
It contains real models, providers, factories, routes, and a Filament panel configuration.
All test-support code that is not part of the production package lives here as real classes —
never as PHPUnit mocks or abstract fixtures.

### Service Provider Pattern

The service provider uses `spatie/laravel-package-tools`. Key boot behaviour:
- Registers Filament assets via `FilamentAsset::register()` (JS/CSS/Alpine components —
  currently commented out until assets are built)
- Registers icons via `FilamentIcon::register()`
- Publishes stubs from `stubs/` to the host app's `stubs/laravel-webdav-server-filament/`

### Frontend Build

`resources/js/index.js` → esbuild → `resources/dist/laravel-webdav-server-filament.js`

### Testing Setup

`tests/TestCase.php` bootstraps all required Filament service providers manually. Uses
`LazilyRefreshDatabase` — migrations only run when a test actually queries the database.

`phpunit.xml` defines `tests/Feature` and `tests/Integration` as separate suites.

## Architectural Decision Records

Binding decisions for this project are documented in `docs/adr/`. All agents must read and follow
the accepted ADRs before writing or reviewing code.

| ADR | Topic |
|-----|-------|
| [0001](docs/adr/0001-test-architecture-and-layering.md) | Test layers — Feature, Integration, Unit; no mocks; workbench as test-support home |
| [0002](docs/adr/0002-class-naming-convention-by-suffix.md) | Class naming by role suffix; no redundant namespace repetition in names |
| [0003](docs/adr/0003-solid-compliance-and-design-patterns.md) | SOLID compliance; prefer established patterns over ad hoc structures |
| [0004](docs/adr/0004-domain-specific-exception-hierarchies.md) | Domain exception hierarchies; no raw SPL exceptions for package failures |
| [0005](docs/adr/0005-method-level-phpdoc-and-import-based-type-references.md) | PHPDoc on public methods; imported short names in docblocks |
| [0006](docs/adr/0006-changelog-maintenance-and-unreleased-entry-policy.md) | Maintain `CHANGELOG.md` under `[Unreleased]` as part of each change |
| [0007](docs/adr/0007-conventional-commits.md) | Commit messages must follow Conventional Commits v1.0.0 |

## Code Style

Pint with PSR-12 preset + `"concat_space": {"spacing": "one"}`. `vendor/`, `workbench/`, and
`database/` are excluded from linting. Always run `composer lint` before committing PHP files.

## Commit Convention

All commits must follow [Conventional Commits v1.0.0](https://www.conventionalcommits.org/en/v1.0.0/)
as described in ADR 0007. Common types for this project: `feat`, `fix`, `docs`, `refactor`,
`test`, `chore`.
