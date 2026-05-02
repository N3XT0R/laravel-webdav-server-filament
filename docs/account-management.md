# Account Management

## Resources

This package ships with two Filament resources for WebDAV account management:

- **Admin resource** — full control over all accounts; for administrators managing access on behalf of users
- **User resource** — self-service access; for authenticated users managing their own accounts directly

Both resources use the same underlying account model and service layer.

---

## Admin Resource

### Purpose

The admin resource is designed for administrators who manage access to WebDAV storage.

The UI makes three things clear:

- who owns the WebDAV account
- whether the account is active
- what connection details the user needs

### Account Creation

When an administrator creates an account, they choose:

- username
- optional display name
- password
- linked application user
- enabled state
- optional metadata

The linked application user is important because notifications and downstream authorization workflows depend on it.
After creation, that relationship is locked in the edit form.

### Account View

The view page is the handoff screen.

It shows account data in read-only form and includes a copyable WebDAV URL. This keeps the operational handoff
simple: the administrator can copy the endpoint without reconstructing it from route or storage configuration.

### Account Editing

The edit page supports operational changes:

- rename the WebDAV username
- change display name
- reset or change password
- enable or disable the account
- update metadata

The linked application user cannot be changed after creation. If the wrong user was selected, create a new WebDAV
account for the correct user and disable or delete the incorrect account according to local policy.

### Password Reset

The reset password action is available from the table and the edit page header.

When notifications are enabled, resetting the password sends a Laravel notification to the linked user. This keeps
the administrator from manually copying the new password into a separate message.

Applications should still define a local security policy for password delivery. For example:

- require administrators to confirm the recipient before resetting
- prefer short-lived support workflows
- require users to store credentials in an approved password manager

### Deletion

Deleting an account removes the WebDAV account record and dispatches lifecycle events.

Deletion should be treated as an administrative action with operational consequences. If your organization needs a
reversible workflow, prefer disabling accounts before deletion.

---

## User Resource (self-service)

### Purpose

The user resource lets authenticated users create and manage their own WebDAV accounts without contacting an
administrator.

It is disabled by default. An administrator must opt in via plugin configuration before it becomes available on a
panel. See [Extending The Package](extending.md#user-resource-self-service) for how to enable it.

### Account Creation

When a user creates an account, they provide:

- username
- optional display name
- password
- enabled state
- optional metadata

The account is automatically linked to the authenticated user. There is no user select field.

### Account View

The view page shows account data in read-only form, including a copyable WebDAV URL — the same as the admin view.

### Account Editing

The edit page supports the same operational changes as the admin resource, except the linked user cannot be changed.

### Deletion

Users can delete their own accounts from the table. Lifecycle events are dispatched on deletion.

### Access Control

The user resource enforces access at page mount level using the callback configured by the developer. This runs
independently of `canAccess()`, so it does not conflict with Filament Shield or other authorization packages that
extend `canAccess()`.

If no callback is configured, the resource is inaccessible regardless of navigation visibility.

---

## Notification Experience

Notifications are enabled by default and cover:

- account creation
- password reset

The messages include the information a user needs to connect, including the WebDAV username and password when
available.

If notifications are disabled, credentials must be delivered through another approved channel.
