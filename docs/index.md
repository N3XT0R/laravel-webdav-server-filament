# Laravel WebDAV Server Filament

This documentation explains how to use the Filament integration for the Laravel WebDAV Server package from two angles:

- **DX**: how developers install, configure, extend, and test the package safely.
- **UX**: how administrators and operators should understand the user-facing workflows in Filament.

The package is intentionally small: it does not replace the core WebDAV server. It provides a Filament resource for
managing WebDAV accounts, account URLs, password workflows, notifications, and lifecycle events.

## Documentation Structure

### Start Here

- [Getting Started](getting-started.md)

Use this when you need the package installed in a Filament panel and want to understand the expected setup flow.

### Developer Experience

- [Developer Experience](developer-experience.md)

Use this when you are integrating the package into application code, customizing behavior, listening to events,
testing changes, or preparing a contribution.

### User Experience

- [User Experience](user-experience.md)

Use this when you are designing the admin workflow for people who create, inspect, reset, disable, or delete WebDAV
accounts in Filament.

### Operations

- [Operations](operations.md)

Use this when you care about notification policy, audit logging, WebDAV endpoint handoff, support workflows, and safe
administration.

### Architecture

- [Architectural Decision Records](adr/index.md)

Use the ADRs when changing code. They define the project rules for testing, naming, exceptions, documentation,
changelog maintenance, and commits.

## Audience

Junior developers should start with Getting Started and then read the DX guide before changing code.

Senior developers should focus on Developer Experience, Operations, and the ADRs. Those sections explain the extension
points and the boundaries that are intentionally exposed for application-level customization.

Administrators, product owners, and support teams should use the UX and Operations sections to understand what the
Filament screens communicate to users and what operational consequences the workflows have.
