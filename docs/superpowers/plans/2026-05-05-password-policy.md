# Password Policy Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add configurable password complexity rules (min length, mixed case, numbers, symbols) to all WebDAV account password fields, backed by a single helper class and package config.

**Architecture:** A `final` helper class `WebDavPasswordRule` reads from package config and returns a Laravel `Password` validation rule. All three resource files (`WebDavAccountResource`, `UserWebDavAccountResource`, `ResetPasswordAction`) consume the same helper for both validation and auto-generated password length.

**Tech Stack:** Laravel `Illuminate\Validation\Rules\Password`, Laravel `Illuminate\Support\Facades\Config`, Filament TextInput `->rules()`, Orchestra Testbench for unit tests.

---

## File Map

| Action | File |
|--------|------|
| Modify | `config/laravel-webdav-server-filament.php` |
| Modify | `phpunit.xml` |
| **Create** | `src/Rules/WebDavPasswordRule.php` |
| Modify | `src/Resources/WebDavAccountResource.php` |
| Modify | `src/Resources/UserWebDavAccountResource.php` |
| Modify | `src/Resources/WebDavAccountResource/Actions/ResetPasswordAction.php` |
| **Create** | `tests/Unit/Rules/WebDavPasswordRuleTest.php` |
| Modify | `tests/Feature/Resources/WebDavAccountResourceTest.php` |
| Modify | `tests/Feature/Resources/UserWebDavAccountResourceTest.php` |

---

### Task 1: Add password section to config and register Unit test suite

**Files:**
- Modify: `config/laravel-webdav-server-filament.php`
- Modify: `phpunit.xml`

- [ ] **Step 1: Add password config section**

Replace the contents of `config/laravel-webdav-server-filament.php` with:

```php
<?php

// config for N3XT0R/LaravelWebdavServerFilament
return [
    'notifications' => [
        'enabled' => true,
    ],

    'password' => [
        'min_length'         => 16,   // minimum password length; also used for generated passwords
        'require_mixed_case' => true, // require both upper- and lowercase letters
        'require_numbers'    => true, // require at least one number
        'require_symbols'    => true, // require at least one symbol
    ],
];
```

- [ ] **Step 2: Add Unit test suite to phpunit.xml**

Add the following `<testsuite>` block inside the `<testsuites>` element in `phpunit.xml`, after the existing `Integration` entry:

```xml
<testsuite name="Unit">
    <directory>tests/Unit</directory>
</testsuite>
```

The `<testsuites>` block should look like:

```xml
<testsuites>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Integration">
        <directory>tests/Integration</directory>
    </testsuite>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
</testsuites>
```

- [ ] **Step 3: Commit**

```bash
git add config/laravel-webdav-server-filament.php phpunit.xml
git commit -m "chore(config): add password policy configuration and unit test suite"
```

---

### Task 2: Write failing unit tests for WebDavPasswordRule

**Files:**
- Create: `tests/Unit/Rules/WebDavPasswordRuleTest.php`

- [ ] **Step 1: Create the test file**

Create `tests/Unit/Rules/WebDavPasswordRuleTest.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Tests\Unit\Rules;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use N3XT0R\LaravelWebdavServerFilament\Rules\WebDavPasswordRule;
use N3XT0R\LaravelWebdavServerFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class WebDavPasswordRuleTest extends TestCase
{
    #[Test]
    public function generated_length_returns_configured_value(): void
    {
        Config::set('laravel-webdav-server-filament.password.min_length', 20);

        self::assertSame(20, WebDavPasswordRule::generatedLength());
    }

    #[Test]
    public function generated_length_returns_default_16_when_not_set(): void
    {
        Config::set('laravel-webdav-server-filament.password', []);

        self::assertSame(16, WebDavPasswordRule::generatedLength());
    }

    #[Test]
    public function validation_rule_rejects_password_shorter_than_min_length(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'Short1!'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertTrue($validator->fails());
    }

    #[Test]
    public function validation_rule_accepts_password_meeting_all_requirements(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => true,
            'require_numbers'    => true,
            'require_symbols'    => true,
        ]);

        $validator = Validator::make(
            ['password' => 'ValidP@ssword123'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertFalse($validator->fails());
    }

    #[Test]
    public function validation_rule_rejects_password_without_mixed_case_when_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => true,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'alllowercasepassword'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertTrue($validator->fails());
    }

    #[Test]
    public function validation_rule_accepts_lowercase_only_when_mixed_case_not_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'alllowercasepassword'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertFalse($validator->fails());
    }

    #[Test]
    public function validation_rule_rejects_password_without_numbers_when_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => true,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'NoNumbersHereAtAll'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertTrue($validator->fails());
    }

    #[Test]
    public function validation_rule_accepts_no_numbers_when_not_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'NoNumbersHereAtAll'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertFalse($validator->fails());
    }

    #[Test]
    public function validation_rule_rejects_password_without_symbols_when_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => true,
        ]);

        $validator = Validator::make(
            ['password' => 'NoSymbolsHere1234'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertTrue($validator->fails());
    }

    #[Test]
    public function validation_rule_accepts_no_symbols_when_not_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'NoSymbolsHere1234'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertFalse($validator->fails());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker exec laravel-webdav-server-filament-php-1 ./vendor/bin/pest --testsuite=Unit
```

Expected: FAIL — `WebDavPasswordRule` class does not exist yet.

---

### Task 3: Implement WebDavPasswordRule

**Files:**
- Create: `src/Rules/WebDavPasswordRule.php`

- [ ] **Step 1: Create the class**

Create `src/Rules/WebDavPasswordRule.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Rules;

use Illuminate\Validation\Rules\Password;

final class WebDavPasswordRule
{
    public static function validationRule(): Password
    {
        $rule = Password::min(self::minLength());

        if ((bool) config('laravel-webdav-server-filament.password.require_mixed_case', true)) {
            $rule = $rule->mixedCase();
        }

        if ((bool) config('laravel-webdav-server-filament.password.require_numbers', true)) {
            $rule = $rule->numbers();
        }

        if ((bool) config('laravel-webdav-server-filament.password.require_symbols', true)) {
            $rule = $rule->symbols();
        }

        return $rule;
    }

    public static function generatedLength(): int
    {
        return self::minLength();
    }

    private static function minLength(): int
    {
        return (int) config('laravel-webdav-server-filament.password.min_length', 16);
    }
}
```

- [ ] **Step 2: Run unit tests to verify they pass**

```bash
docker exec laravel-webdav-server-filament-php-1 ./vendor/bin/pest --testsuite=Unit
```

Expected: all 9 unit tests PASS.

- [ ] **Step 3: Commit**

```bash
git add src/Rules/WebDavPasswordRule.php tests/Unit/Rules/WebDavPasswordRuleTest.php
git commit -m "feat(password-policy): add WebDavPasswordRule helper with unit tests"
```

---

### Task 4: Apply rule to all three resource files

**Files:**
- Modify: `src/Resources/WebDavAccountResource.php`
- Modify: `src/Resources/UserWebDavAccountResource.php`
- Modify: `src/Resources/WebDavAccountResource/Actions/ResetPasswordAction.php`

- [ ] **Step 1: Update WebDavAccountResource**

Add the import at the top of `src/Resources/WebDavAccountResource.php` with the other `use` statements:

```php
use N3XT0R\LaravelWebdavServerFilament\Rules\WebDavPasswordRule;
```

Replace the `password` field definition (lines 63–78) with:

```php
TextInput::make('password')
    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.password'))
    ->password()
    ->revealable()
    ->required(fn (string $operation): bool => $operation === 'create')
    ->rules([WebDavPasswordRule::validationRule()])
    ->suffixAction(
        Action::make('generatePassword')
            ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.actions.generate_password'))
            ->icon(Heroicon::OutlinedKey)
            ->action(function (Set $set): void {
                $password = Str::password(WebDavPasswordRule::generatedLength());

                $set('password', $password);
                $set('password_confirmation', $password);
            }),
    ),
```

- [ ] **Step 2: Update UserWebDavAccountResource**

Add the import at the top of `src/Resources/UserWebDavAccountResource.php`:

```php
use N3XT0R\LaravelWebdavServerFilament\Rules\WebDavPasswordRule;
```

Replace the `password` field definition (lines 98–113) with:

```php
TextInput::make('password')
    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.password'))
    ->password()
    ->revealable()
    ->required(fn (string $operation): bool => $operation === 'create')
    ->rules([WebDavPasswordRule::validationRule()])
    ->suffixAction(
        Action::make('generatePassword')
            ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.actions.generate_password'))
            ->icon(Heroicon::OutlinedKey)
            ->action(function (Set $set): void {
                $password = Str::password(WebDavPasswordRule::generatedLength());

                $set('password', $password);
                $set('password_confirmation', $password);
            }),
    ),
```

- [ ] **Step 3: Update ResetPasswordAction**

Add the import at the top of `src/Resources/WebDavAccountResource/Actions/ResetPasswordAction.php`:

```php
use N3XT0R\LaravelWebdavServerFilament\Rules\WebDavPasswordRule;
```

Replace the `password` field definition (lines 36–41) with:

```php
TextInput::make('password')
    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.new_password'))
    ->password()
    ->revealable()
    ->required()
    ->rules([WebDavPasswordRule::validationRule()])
    ->default(fn (): string => Str::password(WebDavPasswordRule::generatedLength())),
```

- [ ] **Step 4: Run code style fixer**

```bash
docker exec laravel-webdav-server-filament-php-1 composer lint
```

Expected: PASS — no formatting issues.

- [ ] **Step 5: Commit**

```bash
git add src/Resources/WebDavAccountResource.php \
        src/Resources/UserWebDavAccountResource.php \
        src/Resources/WebDavAccountResource/Actions/ResetPasswordAction.php
git commit -m "feat(password-policy): apply WebDavPasswordRule to all password fields"
```

---

### Task 5: Update feature tests — WebDavAccountResourceTest

Existing test passwords that are shorter than 16 characters must be replaced. Use the substitutions below consistently — the same logical password gets the same replacement everywhere (including `Hash::check` assertions and notification content assertions).

| Old password | Chars | New password | Chars |
|---|---|---|---|
| `Secret1234!` | 11 | `ValidP@ssword123` | 16 |
| `Different1234!` | 14 | `OtherP@ssword456` | 16 |
| `NewSecret1234!` | 14 | `N3wP@ssword!5678` | 16 |
| `BrandNew5678!` | 13 | `Br@ndNewP@ss567!` | 16 |
| `HeaderNew5678!` | 14 | `H3aderNewP@ss56!` | 16 |
| `SilentSecret1234!` | 17 | *(keep — already valid)* | — |

**Files:**
- Modify: `tests/Feature/Resources/WebDavAccountResourceTest.php`

- [ ] **Step 1: Apply substitutions**

In `tests/Feature/Resources/WebDavAccountResourceTest.php`, apply each substitution below. Every occurrence of the old string must be replaced — including password values, `Hash::check` assertions, and `assertContains` notification content checks.

Replace `Secret1234!` with `ValidP@ssword123` (all occurrences):
- Line 94: `'password' => 'Secret1234!'`
- Line 262: `'password' => 'Secret1234!'`
- Line 317: `'password' => 'Secret1234!'`
- Line 339: `'password' => 'Secret1234!'`
- Line 277: `Hash::check('Secret1234!', ...)`
- Line 294: `assertContains('Password: Secret1234!', ...)`
- Line 298: `$notification->getPassword() === 'Secret1234!'`

Replace `Different1234!` with `OtherP@ssword456`:
- Line 95: `'password_confirmation' => 'Different1234!'`

Replace `NewSecret1234!` with `N3wP@ssword!5678` (all occurrences):
- Line 203: `'password' => 'NewSecret1234!'`
- Line 204: `'password_confirmation' => 'NewSecret1234!'`
- Line 211: `Hash::check('NewSecret1234!', ...)`
- Line 221: `assertContains('New password: NewSecret1234!', ...)`
- Line 225: `$notification->getPassword() === 'NewSecret1234!'`

Replace `BrandNew5678!` with `Br@ndNewP@ss567!` (all occurrences):
- Line 427: `'password' => 'BrandNew5678!'`
- Line 428: `'password_confirmation' => 'BrandNew5678!'`
- Line 439: `Hash::check('BrandNew5678!', ...)`

Replace `HeaderNew5678!` with `H3aderNewP@ss56!` (all occurrences):
- Line 497: `'password' => 'HeaderNew5678!'`
- Line 498: `'password_confirmation' => 'HeaderNew5678!'`
- Line 505: `Hash::check('HeaderNew5678!', ...)`

- [ ] **Step 2: Run feature tests to verify they pass**

```bash
docker exec laravel-webdav-server-filament-php-1 ./vendor/bin/pest --testsuite=Feature --filter="WebDavAccountResourceTest"
```

Expected: all tests PASS.

---

### Task 6: Update feature tests — UserWebDavAccountResourceTest

**Files:**
- Modify: `tests/Feature/Resources/UserWebDavAccountResourceTest.php`

- [ ] **Step 1: Apply substitution**

In `tests/Feature/Resources/UserWebDavAccountResourceTest.php`:

Replace `Secret1234!` with `ValidP@ssword123`:
- Line 60: `'password' => 'Secret1234!'`

- [ ] **Step 2: Run all tests**

```bash
docker exec laravel-webdav-server-filament-php-1 ./vendor/bin/pest
```

Expected: all tests PASS across Unit and Feature suites.

- [ ] **Step 3: Run code style check**

```bash
docker exec laravel-webdav-server-filament-php-1 composer lint
```

Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Resources/WebDavAccountResourceTest.php \
        tests/Feature/Resources/UserWebDavAccountResourceTest.php
git commit -m "test(password-policy): update test passwords to meet complexity requirements"
```
