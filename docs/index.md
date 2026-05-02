# Laravel WebDAV Server Filament

This package adds Filament-based WebDAV account management to your Laravel application.

It provides two resources:

- an **admin resource** for administrators managing accounts on behalf of users
- a **user resource** for authenticated users managing their own accounts directly (opt-in)

The package does not replace the core WebDAV server. It builds on top of it and exposes its account management
workflow through a Filament panel.

## Documentation Map

### Installation

- [Installation](installation.md)

Install the package, register the plugin, publish configuration, and choose which resources to expose on each panel.

### Account Management

- [Account Management](account-management.md)

Understand the account lifecycle for both the admin and user-facing resources: create, view, edit, reset passwords,
disable, and delete.

### Configuration

- [Installation — Configuration](installation.md#configuration)

Review package configuration and the core WebDAV settings this integration depends on.

### Notifications

- [Extending The Package — Notifications](extending.md#notifications)
- [Operations — Notification Policy](operations.md#notification-policy)

Configure and reason about account creation and password reset notifications.

### Events

- [Extending The Package — Lifecycle Events](extending.md#lifecycle-events)
- [Operations — Event-Driven Audit Logging](operations.md#event-driven-audit-logging)

Use lifecycle events for logging, auditing, metrics, and application-specific reactions.

### Operations

- [Operations](operations.md)

Plan support workflows, credential handoff, logging, security expectations, and WebDAV URL troubleshooting.

### Extending The Package

- [Extending The Package](extending.md)

Customize plugin behavior, enable the user resource, configure the user select field, hook into notifications and
events, and follow testing and contribution rules.

### Architecture

- [Architectural Decision Records](adr/index.md)

Review the accepted project decisions before changing code. They define testing, naming, exceptions, documentation,
changelog, and commit rules.

## Common Workflows

### Add Admin Account Management To A Filament Panel

1. [Installation](installation.md)
2. [Account Management — Admin Resource](account-management.md#admin-resource)
3. [Operations](operations.md)

### Add Self-Service Account Management For Users

1. [Installation — User Self-Service Panel](installation.md#user-self-service-panel)
2. [Account Management — User Resource](account-management.md#user-resource-self-service)
3. [Extending The Package — User Resource](extending.md#user-resource-self-service)

### Customize Behavior In Application Code

1. [Extending The Package](extending.md)
2. [Operations](operations.md)
3. [Architectural Decision Records](adr/index.md)

### Prepare Support And Audit Processes

1. [Account Management](account-management.md)
2. [Operations](operations.md)
3. [Extending The Package — Lifecycle Events](extending.md#lifecycle-events)
