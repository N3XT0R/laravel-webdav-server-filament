# User Experience

## Purpose

The Filament resource is designed for administrators who manage access to WebDAV storage.

The UI should make three things clear:

- who owns the WebDAV account
- whether the account is active
- what connection details the user needs

## Account Creation

When an administrator creates an account, they choose:

- username
- optional display name
- password
- linked application user
- enabled state
- optional metadata

The linked application user is important because notifications and downstream authorization workflows depend on it.
After creation, that relationship is locked in the edit form.

## Account View

The view page is the handoff screen.

It shows account data in read-only form and includes a copyable WebDAV URL. This keeps the operational handoff simple:
the administrator can copy the endpoint without reconstructing it from route or storage configuration.

## Account Editing

The edit page supports operational changes:

- rename the WebDAV username
- change display name
- reset or change password
- enable or disable the account
- update metadata

The linked application user cannot be changed after creation. If the wrong user was selected, create a new WebDAV
account for the correct user and disable or delete the incorrect account according to local policy.

## Password Reset

The reset password action exists in the table and edit page header.

When notifications are enabled, resetting the password sends a Laravel notification to the linked user. This keeps the
administrator from manually copying the new password into a separate message.

Applications should still define a local security policy for password delivery. For example:

- require administrators to confirm the recipient before resetting
- prefer short-lived support workflows
- require users to store credentials in an approved password manager

## Deletion

Deleting an account removes the WebDAV account record and dispatches lifecycle events.

From a UX perspective, deletion should be treated as an administrative action with operational consequences. If your
organization needs a reversible workflow, prefer disabling accounts before deletion.

## Notification Experience

Notifications are enabled by default and cover:

- account creation
- password reset

The messages include the information a user needs to connect, including the WebDAV username and password when available.

If notifications are disabled, administrators must deliver credentials through another approved channel.
