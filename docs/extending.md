# Extending The Package

## Mental Model

This package is an admin integration, not the WebDAV runtime.

The core package owns authentication, storage resolution, path resolution, and request handling. This package owns the
Filament resources and the supporting UI workflows around WebDAV account management.

## Plugin Configuration

Register the plugin per panel:

```php
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;

$panel->plugin(LaravelWebdavServerFilamentPlugin::make());
```

### Admin Resource

The admin-facing resource is registered by default. Disable it when the panel should not expose it:

```php
LaravelWebdavServerFilamentPlugin::make()
    ->withoutAdminAccountResource();
```

### User Resource (self-service)

The user-facing resource is disabled by default. Enable it for all authenticated users:

```php
LaravelWebdavServerFilamentPlugin::make()
    ->withUserAccountResource();
```

Enable it conditionally based on a callback that receives the authenticated user:

```php
LaravelWebdavServerFilamentPlugin::make()
    ->userAccountResourceEnabledUsing(
        fn (User $user): bool => $user->hasVerifiedEmail()
    );
```

The callback runs on every navigation and page mount check. Keep it fast — avoid database queries inside it unless
the result is cached.

The user resource enforces its access check independently of `canAccess()`, which means it is compatible with Filament
Shield and other authorization packages that extend `canAccess()`.

### User Select Field

Customize the user search field in the admin resource:

```php
use Filament\Forms\Components\Select;

LaravelWebdavServerFilamentPlugin::make()
    ->userSelectUsing(function (Select $select): Select {
        return $select->label('Owner');
    });
```

## Notifications

The package sends Laravel notifications for:

- WebDAV account creation
- WebDAV account password reset

Notifications can be disabled globally:

```php
'notifications' => [
    'enabled' => false,
],
```

The notifications use the `mail` channel. They are standard Laravel notification classes, so applications can extend
the idea with additional channels if needed by publishing and overriding the notification classes.

## Lifecycle Events

The package dispatches lifecycle events for:

- account created — `WebDavAccountCreatedEvent`
- account updated — `WebDavAccountUpdatedEvent`
- account deleted — `WebDavAccountDeletedEvent`

All concrete events extend `WebDavAccountEvent` and expose:

- `record`: the affected WebDAV account model
- `action`: the lifecycle action string

Listen to a concrete event for a specific workflow:

```php
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountCreatedEvent;

Event::listen(WebDavAccountCreatedEvent::class, function (WebDavAccountCreatedEvent $event): void {
    // Create an audit entry.
});
```

Listen to the base event for generic observability across all lifecycle actions:

```php
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountEvent;

Event::listen(WebDavAccountEvent::class, function (WebDavAccountEvent $event): void {
    logger()->info('WebDAV account lifecycle event', [
        'action' => $event->action,
        'record_id' => $event->record->getKey(),
    ]);
});
```

## Resource Behavior

### Admin Resource — Create

Creating an account:

- creates the WebDAV account through the core account management service
- links the account to the selected application user
- stores optional metadata
- dispatches lifecycle events
- sends a notification when enabled

The plain password is only available during the create request and is used for notification delivery.

### Admin Resource — Edit

Editing an account:

- can update username, display name, enabled state, metadata, and password
- cannot change the linked application user after creation
- dispatches lifecycle events after successful persistence

### User Resource — Create

Creating an account through the user-facing resource:

- automatically links the account to the currently authenticated user
- does not expose a user select field
- otherwise follows the same service and event workflow as the admin resource

### User Resource — Edit

Editing an account through the user-facing resource:

- supports the same fields as the admin edit page, excluding the user select
- the linked user cannot be changed

### View Page (both resources)

The view page adds a read-only `WebDavUrlInput` component. It resolves the URL through the core package `WebDavPath`
facade and provides a copy action.

### Delete

Delete actions dispatch lifecycle events after successful deletion. Consumers can use the event record for logging
even after the model has been deleted.

## Testing Guidance

Run PHP and Composer commands inside the Docker PHP container:

```bash
docker compose exec php composer test:lint
docker compose exec php vendor/bin/phpunit tests/Feature/Resources/WebDavAccountResourceTest.php
docker compose exec php vendor/bin/phpunit tests/Feature/Resources/UserWebDavAccountResourceTest.php
```

Prefer targeted tests while developing. Run the full suite only when a change crosses multiple boundaries.

When adding behavior:

- use real Laravel, Filament, Eloquent, and notification behavior
- avoid PHPUnit mocks
- place concrete test-support implementations in `workbench/` when needed
- update `CHANGELOG.md` for notable user-facing or developer-facing changes

## Contribution Rules

Before changing code, read the ADRs:

- [Test Architecture And Layering](adr/0001-test-architecture-and-layering.md)
- [Class Naming Convention By Suffix](adr/0002-class-naming-convention-by-suffix.md)
- [SOLID Compliance And Established Design Patterns](adr/0003-solid-compliance-and-design-patterns.md)
- [Domain-Specific Exception Hierarchies](adr/0004-domain-specific-exception-hierarchies.md)
- [Method-Level PHPDoc And Import-Based Type References](adr/0005-method-level-phpdoc-and-import-based-type-references.md)
- [Changelog Maintenance And Unreleased Entry Policy](adr/0006-changelog-maintenance-and-unreleased-entry-policy.md)
- [Conventional Commits](adr/0007-conventional-commits.md)
