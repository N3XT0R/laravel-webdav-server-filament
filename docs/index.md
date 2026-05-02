# Laravel WebDAV Server Filament

The package is intentionally small: it does not replace the core WebDAV server. It provides a Filament resource for
managing WebDAV accounts, account URLs, password workflows, notifications, and lifecycle events.

Use this documentation by topic. Start with installation when adding the package to an application, then move through
configuration, account management, notifications, events, and operations as needed.

## Documentation Map

### Installation

- [Installation](installation.md)

Install the package, register the plugin, publish configuration, and connect the Filament resource to a panel.

### Configuration

- [Installation](installation.md#configuration)

Review package configuration and the core WebDAV settings this integration depends on.

### Account Management

- [Account Management](account-management.md)

Understand the account lifecycle in Filament: create, view, edit, reset passwords, disable, and delete.

### Notifications

- [Extending The Package](extending.md#notifications)
- [Operations](operations.md#notification-policy)

Configure and reason about account creation and password reset notifications.

### Events

- [Extending The Package](extending.md#lifecycle-events)
- [Operations](operations.md#event-driven-audit-logging)

Use lifecycle events for logging, auditing, metrics, and application-specific reactions.

### Operations

- [Operations](operations.md)

Plan support workflows, credential handoff, logging, security expectations, and WebDAV URL troubleshooting.

### Extending The Package

- [Extending The Package](extending.md)

Customize plugin behavior, resource behavior, notifications, events, and test coverage.

### Architecture

- [Architectural Decision Records](adr/index.md)

Review the accepted project decisions before changing code. They define testing, naming, exceptions, documentation,
changelog, and commit rules.

## Common Workflows

### Add The Package To A Filament Panel

1. [Installation](installation.md)
2. [Account Management](account-management.md)
3. [Operations](operations.md)

### Customize Behavior In Application Code

1. [Extending The Package](extending.md)
2. [Operations](operations.md)
3. [Architectural Decision Records](adr/index.md)

### Prepare Support And Audit Processes

1. [Account Management](account-management.md)
2. [Operations](operations.md)
3. [Extending The Package](extending.md)
