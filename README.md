# Laravel WebDAV Server – Filament Integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server-filament.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server-filament)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server-filament/run-tests.yml?branch=5.x&label=tests&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server-filament/actions)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server-filament/php-code-style.yml?branch=5.x&label=code%20style&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server-filament/actions?query=workflow%3A"PHP+code+styling"+branch%3A5.x)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament)

Official Filament plugin for [`n3xt0r/laravel-webdav-server`](https://github.com/N3XT0R/laravel-webdav-server).

Adds WebDAV account management to any Filament panel — for administrators managing accounts on behalf of users, and optionally for users managing their own accounts directly.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.4+ |
| Laravel | 12+ |
| Filament | 5+ |
| `n3xt0r/laravel-webdav-server` | compatible release |

---

## Installation

```bash
composer require n3xt0r/laravel-webdav-server-filament
```

Publish the configuration when you need to change defaults:

```bash
php artisan vendor:publish --tag="laravel-webdav-server-filament-config"
```

---

## Plugin Registration

Register the plugin on any Filament panel:

```php
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;

$panel->plugin(LaravelWebdavServerFilamentPlugin::make());
```

---

## Resources

### Admin Resource

The admin-facing resource is registered by default and gives administrators full control over all WebDAV accounts:

- create accounts and link them to an application user; the linked user cannot be changed after creation
- edit username, display name, password, enabled state, and optional metadata key-value pairs
- reset passwords via a dedicated action in the table and edit page header, with optional notification delivery to the linked user
- view a read-only, copyable WebDAV URL on the account view page
- bulk enable, bulk disable, or bulk delete accounts from the list

To disable it on a panel:

```php
LaravelWebdavServerFilamentPlugin::make()
    ->withoutAdminAccountResource();
```

### User Resource (self-service)

The user-facing resource is **disabled by default**. When enabled, it lets authenticated users manage their own WebDAV accounts directly without admin involvement.

Enable it for all authenticated users:

```php
LaravelWebdavServerFilamentPlugin::make()
    ->withUserAccountResource();
```

Enable it conditionally — for example, only for users with a verified e-mail address:

```php
LaravelWebdavServerFilamentPlugin::make()
    ->userAccountResourceEnabledUsing(
        fn (User $user): bool => $user->hasVerifiedEmail()
    );
```

The user resource:

- scopes all queries to the authenticated user's own accounts — other users' accounts are never visible
- create, edit, view, and delete own accounts; password changes go through the edit form
- does not expose a user select field — `user_id` is set automatically to the authenticated user on creation
- does not include bulk enable/disable — only bulk delete is available
- enforces access at page mount level, independent of `canAccess()`, so it is compatible with Filament Shield and other authorization packages

---

## Plugin API

| Method | Description |
|---|---|
| `withoutAdminAccountResource()` | Disable the admin-facing resource on this panel |
| `withUserAccountResource()` | Enable the user-facing self-service resource for all authenticated users |
| `userAccountResourceEnabledUsing(callable $fn)` | Enable the user-facing resource conditionally via callback |
| `userSelectUsing(callable $fn)` | Customize the user select field in the admin resource |

---

## Customization

### User Select Field

The admin resource includes a searchable user select field by default. Replace it with your own configuration by passing a callback that receives and returns the `Select` component:

```php
use Filament\Forms\Components\Select;
use App\Models\User;

LaravelWebdavServerFilamentPlugin::make()
    ->userSelectUsing(function (Select $select): Select {
        return $select
            ->options(User::active()->pluck('name', 'id'))
            ->searchable();
    });
```

### Notifications

Notifications are enabled by default for account creation and password reset.

Disable globally in `config/laravel-webdav-server-filament.php`:

```php
'notifications' => [
    'enabled' => false,
],
```

### Lifecycle Events

The package dispatches events for every account lifecycle action:

```php
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountCreatedEvent;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountUpdatedEvent;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountDeletedEvent;

// Listen to a specific lifecycle action
Event::listen(WebDavAccountCreatedEvent::class, function (WebDavAccountCreatedEvent $event): void {
    // $event->record is the created WebDavAccountModel
});

// Or listen to the base event for all lifecycle actions
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountEvent;

Event::listen(WebDavAccountEvent::class, function (WebDavAccountEvent $event): void {
    logger()->info('webdav.account.' . $event->action, ['id' => $event->record->getKey()]);
});
```

---

## Documentation

Full documentation is available in the [`docs/`](docs/index.md) directory:

- [Installation](docs/installation.md)
- [Account Management](docs/account-management.md)
- [Extending The Package](docs/extending.md)
- [Operations](docs/operations.md)

---

## Core Package

This package is a companion to the core WebDAV server:

👉 https://github.com/N3XT0R/laravel-webdav-server

Core package documentation:

👉 https://laravel-webdav-server.readthedocs.io/en/latest/

---

## License

MIT License
