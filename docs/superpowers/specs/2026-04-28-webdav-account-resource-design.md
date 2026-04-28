# WebDavAccountResource — Design Spec

**Date:** 2026-04-28  
**Package:** `n3xt0r/laravel-webdav-server-filament`  
**Status:** Approved

## Overview

A Filament Resource for managing `WebDavAccountModel` records. Provides List, Create, Edit, and View pages inside the Filament admin panel. Delegates all business logic to the existing `AccountManagementService` service layer — the Resource acts purely as a UI layer.

---

## File Structure

```
src/
  Resources/
    WebDavAccountResource.php
    WebDavAccountResource/
      Pages/
        ListWebDavAccounts.php
        CreateWebDavAccount.php
        EditWebDavAccount.php
        ViewWebDavAccount.php
      Actions/
        ResetPasswordAction.php
```

---

## Architecture

- `WebDavAccountResource` — Filament Resource class containing form schema, table schema, and page route definitions. No business logic.
- `CreateWebDavAccount` — overrides `handleRecordCreation()` to call `AccountManagementService::create()`.
- `EditWebDavAccount` — overrides `handleRecordUpdate()` to call `AccountManagementService::update()` with an `AccountUpdateDto`.
- `ViewWebDavAccount` — read-only detail page, standard Filament `ViewRecord`.
- `ResetPasswordAction` — reusable `Action` used on both the table row and the Edit page header. Opens a modal with a pre-generated password, calls `AccountManagementService::update()` on confirm.

The Resource does **not** write to Eloquent directly for create/update — it always goes through the service layer to preserve business rules (password hashing, duplicate username checks).

Delete operations go through Filament's standard Eloquent `DeleteAction` — no dedicated service method exists for deletion.

---

## Form Schema

### Create

| Field | Component | Rules |
|-------|-----------|-------|
| `username` | `TextInput` | Required, max:255. `DuplicateUsernameException` is caught and surfaced as a field-level validation error. |
| `display_name` | `TextInput` | Optional. Falls back to username in the service if left empty. |
| `password` | `TextInput` (password, revealable) | Required on Create. Auto-generated value pre-filled via suffix action button. |
| `password_confirmation` | `TextInput` (password, revealable) | Required on Create, must match `password`. |
| `user_id` | `Select` (searchable, configurable) | Required. Default: searches the configured user model by name + email. Configurable via `Plugin::userSelectUsing()`. |
| `enabled` | `Toggle` | Default: `true`. |
| `meta` | `KeyValue` | Optional. JSON key-value editor. |

### Edit

Identical to Create with two differences:
- `password` and `password_confirmation` are **optional**. Leaving them empty means the password is not updated.
- A `ResetPasswordAction` button appears in the page header as an additional entry point.

### Password Generation

On both Create and Edit forms, the password field has a suffix icon action (key icon) that calls `Str::password(16)` and fills both the password and confirmation fields with the same generated value.

---

## Table Schema (ListWebDavAccounts)

### Columns

| Column | Type | Notes |
|--------|------|-------|
| `username` | `TextColumn` | Searchable, sortable |
| `display_name` | `TextColumn` | Searchable, shows `—` when null |
| `user.name` + `user.email` | `TextColumn` | Name as main text, email as description/subtext |
| `enabled` | `IconColumn` (boolean) | Green check / red X |
| `created_at` | `TextColumn` | Sortable, human-readable format |

### Filters

- `enabled` — `TernaryFilter` (all / active / inactive)

### Row Actions

- `ViewAction` — navigates to ViewWebDavAccount page
- `EditAction` — navigates to EditWebDavAccount page
- `ResetPasswordAction` — modal, see below
- `DeleteAction` — standard Filament delete with confirmation

### Bulk Actions

- `DeleteBulkAction` — standard Filament bulk delete
- `EnableBulkAction` — sets `enabled = true` on all selected records via Eloquent mass-update
- `DisableBulkAction` — sets `enabled = false` on all selected records via Eloquent mass-update

---

## ResetPasswordAction

A reusable `Action` class used in two places: table row actions and Edit page header actions.

**Behaviour:**
1. Opens a modal.
2. Modal contains a password field pre-filled with `Str::password(16)` (generated on modal open via `default()`).
3. User can accept the generated password or type a new one. A confirmation field is included.
4. On confirm: calls `AccountManagementService::update($record, new AccountUpdateDto(password: $newPassword))`.
5. Filament success notification is shown.

---

## Plugin Configuration

```php
// Auto-registers the resource (default)
LaravelWebdavServerFilamentPlugin::make()

// Disable auto-registration
LaravelWebdavServerFilamentPlugin::make()
    ->withoutAccountResource()

// Customize the user Select field
LaravelWebdavServerFilamentPlugin::make()
    ->userSelectUsing(fn(Select $select) => $select->relationship('user', 'name'))
```

### Plugin Methods

| Method | Signature | Effect |
|--------|-----------|--------|
| `withoutAccountResource()` | `static` | Sets internal flag; `register()` skips adding the Resource to the panel |
| `userSelectUsing()` | `callable $fn` | Receives the `Select` instance, returns configured `Select`. Stored on the plugin instance; the Resource retrieves it via `LaravelWebdavServerFilamentPlugin::get()->getUserSelectCallback()`. |

### `register()` logic

```php
public function register(Panel $panel): void
{
    if ($this->accountResourceEnabled) {
        $panel->resources([WebDavAccountResource::class]);
    }
}
```

---

## Error Handling

| Exception | Where caught | How surfaced |
|-----------|-------------|-------------|
| `DuplicateUsernameException` | `handleRecordCreation()`, `handleRecordUpdate()` | `$this->addError('data.username', ...)` — shown as inline field error |
| Other exceptions | Not caught — bubble up to Filament's default exception handler | Standard Filament error notification |

---

## Out of Scope

- Editing the `meta` field is supported (KeyValue editor), but no schema enforcement on meta keys — any string key/value is accepted.
- No dedicated delete service method exists; `DeleteAction` uses Eloquent directly.
- No role/permission integration (Filament Shield etc.) — consumers add policies themselves via standard Laravel policy binding.
- The `password_encrypted` column name is an implementation detail of `WebDavAccountModel`; the form always uses the virtual `password` field name and never exposes the stored hash.
