# Laravel WebDAV Server – Filament Integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server-filament.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server-filament)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server-filament/run-tests.yml?branch=5.x&label=tests&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server-filament/actions)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server-filament/php-code-style.yml?branch=5.x&label=code%20style&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server-filament/actions?query=workflow%3A"PHP+code+styling"+branch%3A5.x)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament)

Official Filament plugin for [`n3xt0r/laravel-webdav-server`](https://github.com/N3XT0R/laravel-webdav-server).

Adds WebDAV account management to any Filament panel — for administrators managing accounts on behalf of users, and
optionally for users managing their own accounts directly.

![Laravel WebDAV Server Logo](art/logo.jpg)

---

## Requirements

| Dependency                     | Version            |
|--------------------------------|--------------------|
| PHP                            | 8.4+               |
| Laravel                        | 12+                |
| Filament                       | 5+                 |
| `n3xt0r/laravel-webdav-server` | compatible release |

---

## Installation

```bash
composer require n3xt0r/laravel-webdav-server-filament
```

See [Installation](docs/installation.md) for plugin registration, configuration publishing, and first-use workflows.

---

## Resources

The package ships two Filament resources:

- **Admin resource** — full control over all WebDAV accounts; enabled by default
- **User resource** — self-service access for authenticated users; disabled by default, opt-in via plugin configuration

See [Account Management](docs/account-management.md) for a full description of both resources, their pages, and the account lifecycle.

---

## Configuration

The package is configurable via `config/laravel-webdav-server-filament.php` and covers:

- **Password policy** — minimum length, mixed case, numbers, and symbols requirements for all password fields
- **User resource display** — control whether the meta key/value field is shown on user account forms
- **Notifications** — enable or disable account creation and password reset notifications

See [Installation — Configuration](docs/installation.md#configuration) for all available keys and their defaults.

---

## Plugin API

| Method | Description |
|---|---|
| `withoutAdminAccountResource()` | Disable the admin-facing resource on this panel |
| `withUserAccountResource()` | Enable the user-facing self-service resource for all authenticated users |
| `userAccountResourceEnabledUsing(callable $fn)` | Enable the user-facing resource conditionally via callback |
| `userSelectUsing(callable $fn)` | Customize the user select field in the admin resource |

See [Extending The Package](docs/extending.md) for usage examples and customization options.

---

## Notifications & Events

The package sends notifications on account creation and password reset, and dispatches lifecycle events for every
account action.

See [Extending The Package — Notifications](docs/extending.md#notifications) and
[Extending The Package — Lifecycle Events](docs/extending.md#lifecycle-events).

---

## Documentation

Full documentation: https://laravel-webdav-server-filament.readthedocs.io/en/latest/

Also available locally in the [`docs/`](docs/index.md) directory:

- [Installation](docs/installation.md)
- [Account Management](docs/account-management.md)
- [Extending The Package](docs/extending.md)
- [Operations](docs/operations.md)

---

## Core Package

This package is a companion to the core WebDAV server:

https://github.com/N3XT0R/laravel-webdav-server

Core package documentation:

https://laravel-webdav-server.readthedocs.io/en/latest/

---

## License

MIT License
