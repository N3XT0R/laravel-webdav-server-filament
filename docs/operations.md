# Operations

## Operational Responsibilities

This package provides account management tooling. Your application remains responsible for:

- deciding who may administer WebDAV accounts
- defining password delivery policy
- monitoring lifecycle events
- logging or auditing account changes
- configuring storage spaces in the core WebDAV package

## Notification Policy

Notifications are controlled by:

```php
'notifications' => [
    'enabled' => true,
],
```

When enabled, account creation and password reset send Laravel notifications to the linked user if the user can receive
notifications.

When disabled, no notification is sent. This is useful when credentials must be delivered by an external support desk,
secret manager, or customer onboarding system.

## Event-Driven Audit Logging

Use lifecycle events for audit logs:

- `WebDavAccountCreatedEvent`
- `WebDavAccountUpdatedEvent`
- `WebDavAccountDeletedEvent`
- `WebDavAccountEvent`

Listen to concrete events for specific workflows. Listen to the base event for generic logging.

Example:

```php
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountEvent;

Event::listen(WebDavAccountEvent::class, function (WebDavAccountEvent $event): void {
    audit()->record('webdav.account.' . $event->action, [
        'account_id' => $event->record->getKey(),
        'username' => $event->record->getAttribute('username'),
    ]);
});
```

## Support Workflow

A typical support workflow is:

1. Confirm the target application user.
2. Create the WebDAV account.
3. Confirm notification delivery or manually deliver credentials through an approved channel.
4. Ask the user to connect with the displayed WebDAV URL.
5. Disable the account when access should be suspended.
6. Delete only when the account should no longer exist.

## Security Considerations

Passwords are handled in plain text only during create and reset workflows because they must be shown or delivered once.
The persisted account password is hashed by the core package workflow.

Avoid adding logs that contain plain passwords. If you listen to lifecycle events, log account identifiers and action
metadata, not credential values.

## WebDAV URL Handoff

The account view page uses the core package path resolver to show the WebDAV URL. If the URL is wrong, check the core
package configuration first:

- `webdav-server.route_prefix`
- `webdav-server.storage.default_space`
- `app.url`

The Filament package should not duplicate path resolution rules.
