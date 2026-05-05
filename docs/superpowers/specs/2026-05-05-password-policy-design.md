# Password Policy Design

**Date:** 2026-05-05
**Status:** Approved

## Overview

Introduce configurable password complexity rules for all WebDAV account password fields. Rules are defined once in a static helper class and applied consistently across admin create/edit, user self-service create/edit, and the admin password reset action. Auto-generated passwords use the same configured minimum length.

## Config

A new `password` section is added to `config/laravel-webdav-server-filament.php`:

```php
'password' => [
    'min_length'         => 16,   // minimum password length; also used for generated passwords
    'require_mixed_case' => true, // require both upper- and lowercase letters
    'require_numbers'    => true, // require at least one number
    'require_symbols'    => true, // require at least one symbol
],
```

All keys have defaults matching the current behavior (`min_length: 16`, all requirements enabled).

## New Class: `WebDavPasswordRule`

**File:** `src/Rules/WebDavPasswordRule.php`

A `final` class with two public static methods:

- `validationRule(): \Illuminate\Validation\Rules\Password` — builds a Laravel `Password` rule from config
- `generatedLength(): int` — returns `min_length` from config for use with `Str::password()`

A private `minLength(): int` method consolidates the config read used by both public methods.

Rules are conditionally applied based on their respective config flags. All flags default to `true` if absent.

## Affected Files

### `config/laravel-webdav-server-filament.php`
- Add `password` section with four keys and defaults.

### `src/Rules/WebDavPasswordRule.php` *(new)*
- Implement `validationRule()`, `generatedLength()`, `minLength()`.

### `src/Resources/WebDavAccountResource.php`
- Add `->rules([WebDavPasswordRule::validationRule()])` to the `password` field.
- Replace `Str::password(16)` with `Str::password(WebDavPasswordRule::generatedLength())`.

### `src/Resources/UserWebDavAccountResource.php`
- Same changes as `WebDavAccountResource.php`.

### `src/Resources/WebDavAccountResource/Actions/ResetPasswordAction.php`
- Add `->rules([WebDavPasswordRule::validationRule()])` to the `password` field.
- Replace `Str::password(16)` with `Str::password(WebDavPasswordRule::generatedLength())`.

## Out of Scope

- `password_confirmation` fields — no rule changes needed; they only confirm the primary field.
- Core package (`laravel-webdav-server`) — validation lives in the UI layer, not the persistence layer.
- Separate `require_uppercase` / `require_lowercase` flags — Laravel's `Password` rule exposes only `mixedCase()`, which covers both together.

## Testing

- Unit tests for `WebDavPasswordRule`: verify the correct `Password` rule is built for all config combinations (all flags on, all off, partial).
- Update or extend existing feature tests for `WebDavAccountResource` and `UserWebDavAccountResource` to assert that weak passwords (too short, missing complexity) are rejected.
- Verify that auto-generated passwords satisfy the validation rule.
