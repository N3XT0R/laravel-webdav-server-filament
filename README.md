# Laravel WebDAV Server – Filament Integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server-filament.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server-filament)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server-filament/run-tests.yml?branch=5.x&label=tests&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server-filament/actions)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server-filament/php-code-style.yml?branch=5.x&label=code%20style&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server-filament/actions?query=workflow%3A"PHP+code+styling"+branch%3A5.x)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server-filament)

Official Filament admin panel for [`laravel-webdav-server`](https://github.com/N3XT0R/laravel-webdav-server).

Manage WebDAV accounts, storage access, and configuration through a structured UI.

---

## Overview

This package provides a Filament-based admin interface for the Laravel WebDAV Server.

It allows you to manage WebDAV-related configuration and entities without interacting directly with the database or
configuration files.

The integration is designed to work on top of the stable extension points of the core package.

---

## Requirements

- PHP 8.4+
- Laravel 12+
- Filament 5+
- [`n3xt0r/laravel-webdav-server`](https://github.com/N3XT0R/laravel-webdav-server)

---

## Installation

```bash
composer require n3xt0r/laravel-webdav-server-filament
```

If needed, publish configuration:

```bash
php artisan vendor:publish --tag="laravel-webdav-server-filament-config"
```

---

## Features

- Manage WebDAV accounts (create, update, disable)
- Inspect account configuration used for authentication
- Display WebDAV endpoint URLs per account
- Quick access to connection details (username / endpoint)
- Integration with existing storage and authorization configuration
- Designed to work with the package's account model (`webdav-server.auth.account_model`)

---

## Available Resources

### WebDAV Accounts

A Filament resource for managing WebDAV accounts:

- create and update accounts
- activate / deactivate accounts
- view account identifiers used during authentication
- inspect related storage access

---

## Example Usage

After installation, navigate to your Filament panel.

A new section for WebDAV management will be available.

Typical workflow:

1. Create a new WebDAV account
2. Assign or verify storage configuration
3. Use the displayed endpoint:

```text
https://your-domain.test/webdav/default/
```

4. Connect using a WebDAV client (e.g. WinSCP, Finder, Windows Explorer)

---

## Integration Details

This package does not replace any core functionality.

It builds on top of:

- `AccountRepositoryInterface`
- `CredentialValidatorInterface`
- `PathAuthorizationInterface`

All behavior remains configurable through the core package.

---

## Customization

You can extend or override Filament resources as needed:

- extend the provided Resource classes
- customize forms, tables, and actions
- integrate with your existing Filament panels

---

## Relationship to Core Package

This package is a companion to:

👉 https://github.com/N3XT0R/laravel-webdav-server

The core package provides:

- WebDAV server runtime
- request pipeline
- storage resolution
- authentication and authorization

This package provides:

- administrative UI
- account management
- operational visibility

---

## Documentation

For full WebDAV server documentation, see:

👉 https://laravel-webdav-server.readthedocs.io/en/latest/

---

## License

MIT License
