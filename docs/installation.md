# Installation

## Purpose

`n3xt0r/laravel-webdav-server-filament` adds a Filament admin surface for the core Laravel WebDAV Server package.

Use it when your application already uses Filament and you want to manage WebDAV accounts through a panel — either
by administrators on behalf of users, or by users managing their own accounts directly.

## Requirements

- PHP 8.4 or newer
- Laravel 12 or newer
- Filament 5 or newer
- `n3xt0r/laravel-webdav-server`

## Installation

Install the package with Composer:

```bash
composer require n3xt0r/laravel-webdav-server-filament
```

Publish the configuration when you need to change defaults:

```bash
php artisan vendor:publish --tag="laravel-webdav-server-filament-config"
```

## Plugin Registration

Register the plugin on the Filament panel where WebDAV accounts should be managed:

```php
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;

$panel->plugin(LaravelWebdavServerFilamentPlugin::make());
```

This registers the admin-facing resource by default.

### Admin-only panel

No additional configuration is needed for a standard admin panel:

```php
$panel->plugin(LaravelWebdavServerFilamentPlugin::make());
```

### User self-service panel

To allow authenticated users to manage their own accounts, enable the user resource:

```php
// All authenticated users
$panel->plugin(
    LaravelWebdavServerFilamentPlugin::make()
        ->withoutAdminAccountResource()
        ->withUserAccountResource()
);

// Conditionally — for example, only verified users
$panel->plugin(
    LaravelWebdavServerFilamentPlugin::make()
        ->withoutAdminAccountResource()
        ->userAccountResourceEnabledUsing(
            fn (User $user): bool => $user->hasVerifiedEmail()
        )
);
```

### Both resources on the same panel

If both admin and user resources should appear on the same panel, omit `withoutAdminAccountResource()`:

```php
$panel->plugin(
    LaravelWebdavServerFilamentPlugin::make()
        ->withUserAccountResource()
);
```

## Configuration

The package configuration is published as `config/laravel-webdav-server-filament.php`.

The notification system is enabled by default:

```php
return [
    'notifications' => [
        'enabled' => true,
    ],
];
```

Set `notifications.enabled` to `false` when account creation and password reset notifications should not be sent.

## Core Package Configuration

This Filament integration reads the core package configuration for account and user behavior. The most important core
values are:

- `webdav-server.auth.account_model`
- `webdav-server.auth.user_model`
- `webdav-server.auth.user_id_column`
- `webdav-server.storage.default_space`
- `webdav-server.route_prefix`

The WebDAV URL shown on the view page is resolved through the core package `WebDavPath` facade.

## First Workflow

### Admin

1. Open the configured Filament panel.
2. Go to the WebDAV accounts resource.
3. Create an account and link it to an application user.
4. Copy the WebDAV URL from the account view page.
5. Provide the URL, username, and password to the intended user.

After creation, the linked application user cannot be changed from the edit page.

### User (self-service)

1. Open the Filament panel where the user resource is enabled.
2. Go to the WebDAV accounts resource.
3. Create an account — it is automatically linked to your user.
4. Copy the WebDAV URL from the account view page.
5. Connect with a WebDAV client using the displayed URL, username, and password.
