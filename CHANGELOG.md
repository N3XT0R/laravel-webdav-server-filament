# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Page descriptions on user account resource pages**
    - Added a descriptive subheading to the account list page explaining the purpose of WebDAV accounts to end users.
    - Added a descriptive subheading to the account create page guiding users through the account creation process.
    - Both descriptions are translatable via `resources.accounts.pages.list.description` and
      `resources.accounts.pages.create.description` in the package language files.

- **Configurable meta field visibility on user account forms**
    - Added `laravel-webdav-server-filament.user_resource.show_meta` to control whether the meta key/value field is
      shown on user account forms (default: `false`).

- **Password policy for WebDAV accounts**
    - Added `laravel-webdav-server-filament.password.min_length` to configure the minimum password length (default:
      `16`).
    - Added `laravel-webdav-server-filament.password.require_mixed_case` to require upper- and lowercase characters (
      default: `true`).
    - Added `laravel-webdav-server-filament.password.require_numbers` to require at least one digit (default: `true`).
    - Added `laravel-webdav-server-filament.password.require_symbols` to require at least one symbol (default: `true`).
    - Password fields in all account forms and the password reset action enforce the configured policy.
    - Auto-generated passwords use `min_length` as their length.

## [1.0.1] - 2026-05-05

### Fixed

- **filament resource**
    - Fixed the `WebDavUrlInput` component generating an incorrect WebDAV URL by appending the account ID to the space
      mount URL (e.g. `/webdav/default/2`). The displayed URL is now the correct space mount URL (e.g.
      `/webdav/default`).

## [1.0.0] - 2026-05-02

### Added

- **filament resource**
    - Added a Filament resource for managing WebDAV accounts through list, create, edit, and view pages.
    - Added reusable password reset actions and bulk enable/disable controls for WebDAV account records.
    - Added plugin configuration via `withoutAdminAccountResource()` for disabling the account resource and customizing
      the linked user select field.
    - Added translation-backed labels and messages for the WebDAV account management UI.
    - Added a read-only, copyable WebDAV URL field to the account view page.
    - Added a reusable Filament `WebDavUrlInput` component for displaying copyable account WebDAV URLs.
    - Added WebDAV account lifecycle events for create, update, and delete actions.
    - Added enforcement to prevent changing the linked Laravel user after a WebDAV account has been created.
    - Added a Laravel notification to the linked user when a WebDAV account password is reset.
    - Added a Laravel notification with account, user, timestamp, and password details when a WebDAV account is created.

- **user-facing account resource**
    - Added a self-service Filament resource that lets authenticated users manage their own WebDAV accounts without
      admin access.
    - Added `withUserAccountResource()` plugin method to enable the user resource for all authenticated users.
    - Added `userAccountResourceEnabledUsing(callable $fn)` plugin method to enable the user resource conditionally
      based on the authenticated user.
    - The user resource is disabled by default and must be explicitly opted in; it is compatible with Filament Shield
      and other authorization packages.
    - Pages of the user resource enforce access at mount time independently of `canAccess()` to avoid conflicts with
      third-party authorization.

- **localization**
    - Added German translations for the WebDAV account management UI.

- **configuration**
    - Added a `notifications.enabled` configuration flag for enabling or disabling WebDAV account notifications.

- **documentation**
    - Added structured documentation for getting started, developer experience, user experience, and operations.
    - Reworked documentation navigation around tasks and workflows instead of reader personas.
    - Renamed documentation sections around natural package topics such as installation, account management, extension,
      and operations.

