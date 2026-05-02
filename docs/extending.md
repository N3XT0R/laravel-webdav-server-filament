# Extending The Package

## Mental Model

This package is an admin integration, not the WebDAV runtime.

The core package owns authentication, storage resolution, path resolution, and request handling. This package owns the
Filament resource and the supporting UI workflows around WebDAV account management.

## Main Extension Points

### Plugin Configuration

Register the plugin per panel:

```php
LaravelWebdavServerFilamentPlugin::make()
```

You can disable the account resource:

```php
LaravelWebdavServerFilamentPlugin::make()
    ->withoutAccountResource();
```

You can customize the user select field:

```php
LaravelWebdavServerFilamentPlugin::make()
    ->userSelectUsing(function (Select $select): Select {
        return $select->label('Owner');
    });
```

### Notifications

The package sends Laravel notifications for:

- WebDAV account creation
- WebDAV account password reset

Notifications can be disabled globally:

```php
'notifications' => [
    'enabled' => false,
],
```

The notifications use the `mail` channel today. They are Laravel notification classes, so applications can extend the
idea with additional channels if needed.

### Lifecycle Events

The package dispatches lifecycle events for:

- account created
- account updated
- account deleted

Concrete events extend `WebDavAccountEvent` and expose:

- `record`: the affected WebDAV account model
- `action`: the lifecycle action

Applications can listen to concrete events when behavior should be specific:

```php
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountCreatedEvent;

Event::listen(WebDavAccountCreatedEvent::class, function (WebDavAccountCreatedEvent $event): void {
    // Create an audit entry.
});
```

Applications can also listen to the base event channel:

```php
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountEvent;

Event::listen(WebDavAccountEvent::class, function (WebDavAccountEvent $event): void {
    logger()->info('WebDAV account lifecycle event', [
        'action' => $event->action,
        'record_id' => $event->record->getKey(),
    ]);
});
```

This is useful for generic audit logging, metrics, or system-wide observability.

## Resource Behavior

### Create

Creating an account:

- creates the WebDAV account through the core account management service
- links the account to the selected application user
- stores optional metadata
- dispatches lifecycle events
- sends a notification when enabled

The plain password is only available during the create request and is used for notification delivery.

### Edit

Editing an account:

- can update username, display name, enabled state, metadata, and password
- cannot change the linked application user after creation
- dispatches lifecycle events after successful persistence

### View

The view page adds a read-only `WebDavUrlInput` component. It resolves the URL through the core package `WebDavPath`
facade and provides a copy action.

### Delete

Delete actions dispatch lifecycle events after successful deletion. Consumers can use the event record for logging even
after the model has been deleted.

## Testing Guidance

Run PHP and Composer commands inside the Docker PHP container:

```bash
docker compose exec -T php composer test:lint
docker compose exec -T php vendor/bin/phpunit tests/Feature/Resources/WebDavAccountResourceTest.php
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
