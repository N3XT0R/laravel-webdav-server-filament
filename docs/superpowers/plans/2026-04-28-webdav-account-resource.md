# WebDavAccountResource Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a fully functional Filament Resource for managing WebDAV accounts (`WebDavAccountModel`) with List, Create, Edit, and View pages, delegating all business logic to the existing `AccountManagementService` layer.

**Architecture:** `WebDavAccountResource` is a pure UI layer — form schema, table schema, page registration. Page classes override Filament's lifecycle hooks (`handleRecordCreation`, `handleRecordUpdate`) to delegate to `AccountManagementService`. A reusable `ResetPasswordAction` is used in both the table and the Edit page header. The plugin gets two new fluent configuration methods: `withoutAccountResource()` and `userSelectUsing()`.

**Tech Stack:** PHP 8.2+, Laravel 12+, Filament 5, Pest, SQLite in-memory (tests), `n3xt0r/laravel-webdav-server` service layer (`AccountManagementService`, `AccountUpdateDto`, `DuplicateUsernameException`).

---

## File Map

| File | Action | Responsibility |
|------|--------|---------------|
| `src/LaravelWebdavServerFilamentPlugin.php` | Modify | Add `withoutAccountResource()`, `userSelectUsing()`, `getUserSelectCallback()`, update `register()` |
| `workbench/app/Providers/Filament/AdminPanelProvider.php` | Modify | Add plugin to workbench panel so tests can run against a real panel |
| `src/Resources/WebDavAccountResource.php` | Create | Form schema, table schema, page registration, navigation |
| `src/Resources/WebDavAccountResource/Pages/ListWebDavAccounts.php` | Create | Standard list page (inherits everything from Filament) |
| `src/Resources/WebDavAccountResource/Pages/CreateWebDavAccount.php` | Create | Overrides `handleRecordCreation()` → delegates to `AccountManagementService::create()` |
| `src/Resources/WebDavAccountResource/Pages/EditWebDavAccount.php` | Create | Overrides `handleRecordUpdate()` → delegates to `AccountManagementService::update()`. Header `ResetPasswordAction` |
| `src/Resources/WebDavAccountResource/Pages/ViewWebDavAccount.php` | Create | Standard read-only view page |
| `src/Resources/WebDavAccountResource/Actions/ResetPasswordAction.php` | Create | Table `Action` that opens a modal with auto-generated password and calls `AccountManagementService::update()` |
| `tests/Feature/PluginConfigurationTest.php` | Create | Tests for plugin `withoutAccountResource()` and `userSelectUsing()` |
| `tests/Feature/Resources/WebDavAccountResourceTest.php` | Create | Form, table, create, edit, view, reset-password tests |

---

## Task 1: Plugin — resource toggle & user-select configuration

**Files:**
- Modify: `src/LaravelWebdavServerFilamentPlugin.php`
- Create: `tests/Feature/PluginConfigurationTest.php`

- [ ] **Step 1.1: Write the failing tests**

Create `tests/Feature/PluginConfigurationTest.php`:

```php
<?php

declare(strict_types=1);

use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;
use Filament\Forms\Components\Select;
use Filament\Panel;

it('registers WebDavAccountResource in the panel by default', function (): void {
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('resources')
        ->once()
        ->with([WebDavAccountResource::class])
        ->andReturnSelf();

    $plugin = LaravelWebdavServerFilamentPlugin::make();
    $plugin->register($panel);
});

it('skips registering WebDavAccountResource when withoutAccountResource is called', function (): void {
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('resources')->never();

    $plugin = LaravelWebdavServerFilamentPlugin::make()->withoutAccountResource();
    $plugin->register($panel);
});

it('stores and retrieves the userSelectUsing callback', function (): void {
    $callback = fn(Select $select) => $select->label('Custom User');
    $plugin = LaravelWebdavServerFilamentPlugin::make()->userSelectUsing($callback);

    expect($plugin->getUserSelectCallback())->toBe($callback);
});

it('returns null for userSelectCallback when not configured', function (): void {
    $plugin = LaravelWebdavServerFilamentPlugin::make();
    expect($plugin->getUserSelectCallback())->toBeNull();
});
```

- [ ] **Step 1.2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Feature/PluginConfigurationTest.php -v
```

Expected: FAIL — `withoutAccountResource`, `getUserSelectCallback`, `userSelectUsing` not defined.

- [ ] **Step 1.3: Implement plugin methods**

Replace the full content of `src/LaravelWebdavServerFilamentPlugin.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Panel;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

class LaravelWebdavServerFilamentPlugin implements Plugin
{
    private bool $accountResourceEnabled = true;

    private ?Closure $userSelectCallback = null;

    public function getId(): string
    {
        return 'laravel-webdav-server-filament';
    }

    public function register(Panel $panel): void
    {
        if ($this->accountResourceEnabled) {
            $panel->resources([WebDavAccountResource::class]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function withoutAccountResource(): static
    {
        $this->accountResourceEnabled = false;

        return $this;
    }

    public function userSelectUsing(callable $fn): static
    {
        $this->userSelectCallback = $fn instanceof Closure ? $fn : Closure::fromCallable($fn);

        return $this;
    }

    public function getUserSelectCallback(): ?Closure
    {
        return $this->userSelectCallback;
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
```

- [ ] **Step 1.4: Run tests to confirm pass**

```bash
./vendor/bin/pest tests/Feature/PluginConfigurationTest.php -v
```

Expected: 4 tests PASS.

- [ ] **Step 1.5: Commit**

```bash
git add src/LaravelWebdavServerFilamentPlugin.php tests/Feature/PluginConfigurationTest.php
git commit -m "feat: add withoutAccountResource and userSelectUsing to plugin"
```

---

## Task 2: Workbench panel + resource skeleton

**Files:**
- Modify: `workbench/app/Providers/Filament/AdminPanelProvider.php`
- Create: `src/Resources/WebDavAccountResource.php`
- Create: `src/Resources/WebDavAccountResource/Pages/ListWebDavAccounts.php`
- Create: `src/Resources/WebDavAccountResource/Pages/CreateWebDavAccount.php`
- Create: `src/Resources/WebDavAccountResource/Pages/EditWebDavAccount.php`
- Create: `src/Resources/WebDavAccountResource/Pages/ViewWebDavAccount.php`

- [ ] **Step 2.1: Register the plugin in the workbench panel**

Modify `workbench/app/Providers/Filament/AdminPanelProvider.php` — add imports and the plugin line:

```php
<?php

namespace Workbench\App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->plugin(LaravelWebdavServerFilamentPlugin::make())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

- [ ] **Step 2.2: Create the page stubs**

Create `src/Resources/WebDavAccountResource/Pages/ListWebDavAccounts.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

final class ListWebDavAccounts extends ListRecords
{
    protected static string $resource = WebDavAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
```

Create `src/Resources/WebDavAccountResource/Pages/CreateWebDavAccount.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

final class CreateWebDavAccount extends CreateRecord
{
    protected static string $resource = WebDavAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Implemented in Task 5
        return parent::handleRecordCreation($data);
    }
}
```

Create `src/Resources/WebDavAccountResource/Pages/EditWebDavAccount.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

final class EditWebDavAccount extends EditRecord
{
    protected static string $resource = WebDavAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Implemented in Task 6
        return parent::handleRecordUpdate($record, $data);
    }
}
```

Create `src/Resources/WebDavAccountResource/Pages/ViewWebDavAccount.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

final class ViewWebDavAccount extends ViewRecord
{
    protected static string $resource = WebDavAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
```

- [ ] **Step 2.3: Create the resource skeleton**

Create `src/Resources/WebDavAccountResource.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources;

use Filament\Resources\Resource;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

final class WebDavAccountResource extends Resource
{
    protected static ?string $model = WebDavAccountModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'WebDAV Accounts';

    protected static ?string $modelLabel = 'WebDAV Account';

    protected static ?string $pluralModelLabel = 'WebDAV Accounts';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);  // filled in Task 3
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table->columns([]); // filled in Task 4
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWebDavAccounts::route('/'),
            'create' => Pages\CreateWebDavAccount::route('/create'),
            'view'   => Pages\ViewWebDavAccount::route('/{record}'),
            'edit'   => Pages\EditWebDavAccount::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 2.4: Verify the existing test suite still passes**

```bash
./vendor/bin/pest -v
```

Expected: All tests PASS (no regressions).

- [ ] **Step 2.5: Commit**

```bash
git add workbench/app/Providers/Filament/AdminPanelProvider.php \
    src/Resources/WebDavAccountResource.php \
    src/Resources/WebDavAccountResource/Pages/ListWebDavAccounts.php \
    src/Resources/WebDavAccountResource/Pages/CreateWebDavAccount.php \
    src/Resources/WebDavAccountResource/Pages/EditWebDavAccount.php \
    src/Resources/WebDavAccountResource/Pages/ViewWebDavAccount.php
git commit -m "feat: add WebDavAccountResource skeleton and page stubs"
```

---

## Task 3: Form schema

**Files:**
- Modify: `src/Resources/WebDavAccountResource.php` (fill `form()` method)
- Create: `tests/Feature/Resources/WebDavAccountResourceTest.php` (form section)

- [ ] **Step 3.1: Write failing form tests**

Create `tests/Feature/Resources/WebDavAccountResourceTest.php`:

```php
<?php

declare(strict_types=1);

use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages\CreateWebDavAccount;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages\EditWebDavAccount;
use N3XT0R\LaravelWebdavServerFilament\Tests\DatabaseTestCase;
use Workbench\App\Models\User;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use Illuminate\Support\Facades\Hash;

uses(DatabaseTestCase::class);

// ─── Form field presence ───────────────────────────────────────────────────────

it('renders all expected fields on the create form', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(CreateWebDavAccount::class)
        ->assertFormFieldExists('username')
        ->assertFormFieldExists('display_name')
        ->assertFormFieldExists('password')
        ->assertFormFieldExists('password_confirmation')
        ->assertFormFieldExists('user_id')
        ->assertFormFieldExists('enabled')
        ->assertFormFieldExists('meta');
});

it('renders all expected fields on the edit form', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'test-user',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
        'display_name'       => 'Test User',
        'meta'               => null,
    ]);

    livewire(EditWebDavAccount::class, ['record' => $account->getKey()])
        ->assertFormFieldExists('username')
        ->assertFormFieldExists('display_name')
        ->assertFormFieldExists('password')
        ->assertFormFieldExists('password_confirmation')
        ->assertFormFieldExists('user_id')
        ->assertFormFieldExists('enabled')
        ->assertFormFieldExists('meta');
});

// ─── Required field validation (create) ──────────────────────────────────────

it('requires username on create', function (): void {
    $this->actingAs(User::factory()->create());
    $user = User::factory()->create();

    livewire(CreateWebDavAccount::class)
        ->fillForm([
            'username'             => '',
            'password'             => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
            'user_id'              => $user->id,
            'enabled'              => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['username' => 'required']);
});

it('requires password on create', function (): void {
    $this->actingAs(User::factory()->create());
    $user = User::factory()->create();

    livewire(CreateWebDavAccount::class)
        ->fillForm([
            'username'             => 'newuser',
            'password'             => '',
            'password_confirmation' => '',
            'user_id'              => $user->id,
            'enabled'              => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['password' => 'required']);
});

it('requires user_id on create', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(CreateWebDavAccount::class)
        ->fillForm([
            'username'             => 'newuser',
            'password'             => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
            'user_id'              => null,
            'enabled'              => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['user_id' => 'required']);
});

it('requires password_confirmation to match password on create', function (): void {
    $this->actingAs(User::factory()->create());
    $user = User::factory()->create();

    livewire(CreateWebDavAccount::class)
        ->fillForm([
            'username'             => 'newuser',
            'password'             => 'Secret1234!',
            'password_confirmation' => 'Different!',
            'user_id'              => $user->id,
            'enabled'              => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['password_confirmation']);
});
```

- [ ] **Step 3.2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php -v
```

Expected: FAIL — form fields not defined yet.

- [ ] **Step 3.3: Implement the form schema**

Replace the `form()` method in `src/Resources/WebDavAccountResource.php`:

```php
public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
{
    return $form->schema([
        \Filament\Forms\Components\TextInput::make('username')
            ->label('Username')
            ->required()
            ->maxLength(255)
            ->unique(
                table: 'webdav_accounts',
                column: 'username',
                ignoreRecord: true,
            ),

        \Filament\Forms\Components\TextInput::make('display_name')
            ->label('Display Name')
            ->maxLength(255)
            ->placeholder('Falls back to username if left empty'),

        \Filament\Forms\Components\TextInput::make('password')
            ->label('Password')
            ->password()
            ->revealable()
            ->required(fn(string $operation): bool => $operation === 'create')
            ->suffixAction(
                \Filament\Forms\Components\Actions\Action::make('generatePassword')
                    ->label('Generate')
                    ->icon('heroicon-o-key')
                    ->action(function (\Filament\Forms\Set $set): void {
                        $password = \Illuminate\Support\Str::password(16);
                        $set('password', $password);
                        $set('password_confirmation', $password);
                    }),
            ),

        \Filament\Forms\Components\TextInput::make('password_confirmation')
            ->label('Confirm Password')
            ->password()
            ->revealable()
            ->required(fn(string $operation): bool => $operation === 'create')
            ->same('password'),

        static::buildUserSelect(),

        \Filament\Forms\Components\Toggle::make('enabled')
            ->label('Enabled')
            ->default(true),

        \Filament\Forms\Components\KeyValue::make('meta')
            ->label('Meta')
            ->keyLabel('Key')
            ->valueLabel('Value')
            ->reorderable(),
    ]);
}

private static function buildUserSelect(): \Filament\Forms\Components\Select
{
    $select = \Filament\Forms\Components\Select::make('user_id')
        ->label('User')
        ->required();

    try {
        $callback = \N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin::get()
            ->getUserSelectCallback();
    } catch (\Throwable) {
        $callback = null;
    }

    if ($callback !== null) {
        return $callback($select);
    }

    $userModel = config('webdav-server.auth.user_model');

    return $select
        ->searchable()
        ->getSearchResultsUsing(function (string $search) use ($userModel): array {
            return $userModel::query()
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->limit(50)
                ->pluck('name', 'id')
                ->toArray();
        })
        ->getOptionLabelUsing(
            fn(mixed $value) => $userModel::find($value)?->name ?? (string) $value,
        );
}
```

- [ ] **Step 3.4: Run tests to confirm pass**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php -v
```

Expected: All tests PASS.

- [ ] **Step 3.5: Commit**

```bash
git add src/Resources/WebDavAccountResource.php tests/Feature/Resources/WebDavAccountResourceTest.php
git commit -m "feat: implement WebDavAccountResource form schema with validation"
```

---

## Task 4: Table schema

**Files:**
- Modify: `src/Resources/WebDavAccountResource.php` (fill `table()` method)
- Modify: `tests/Feature/Resources/WebDavAccountResourceTest.php` (add table tests)

- [ ] **Step 4.1: Write failing table tests**

Append to `tests/Feature/Resources/WebDavAccountResourceTest.php`:

```php
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages\ListWebDavAccounts;

// ─── Table ────────────────────────────────────────────────────────────────────

it('lists webdav accounts in the table', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'listed-user',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
        'display_name'       => 'Listed User',
        'meta'               => null,
    ]);

    livewire(ListWebDavAccounts::class)
        ->assertCanSeeTableRecords([$account]);
});

it('can filter accounts by enabled state', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $active = WebDavAccountModel::create([
        'username'           => 'active-user',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
    ]);

    $inactive = WebDavAccountModel::create([
        'username'           => 'inactive-user',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => false,
        'user_id'            => $user->id,
    ]);

    livewire(ListWebDavAccounts::class)
        ->filterTable('enabled', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});

it('can bulk-enable selected accounts', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'disabled-user',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => false,
        'user_id'            => $user->id,
    ]);

    livewire(ListWebDavAccounts::class)
        ->callTableBulkAction('enable', [$account]);

    expect($account->fresh()->enabled)->toBeTrue();
});

it('can bulk-disable selected accounts', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'active-bulk-user',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
    ]);

    livewire(ListWebDavAccounts::class)
        ->callTableBulkAction('disable', [$account]);

    expect($account->fresh()->enabled)->toBeFalse();
});

it('can delete an account from the table', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'to-delete',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
    ]);

    livewire(ListWebDavAccounts::class)
        ->callTableAction('delete', $account);

    $this->assertModelMissing($account);
});
```

- [ ] **Step 4.2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php --filter="table|filter|bulk|delete" -v
```

Expected: FAIL — table columns/filters/bulk actions not defined.

- [ ] **Step 4.3: Implement the table schema**

Replace the `table()` method in `src/Resources/WebDavAccountResource.php`:

```php
public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
{
    return $table
        ->columns([
            \Filament\Tables\Columns\TextColumn::make('username')
                ->label('Username')
                ->searchable()
                ->sortable(),

            \Filament\Tables\Columns\TextColumn::make('display_name')
                ->label('Display Name')
                ->searchable()
                ->default('—'),

            \Filament\Tables\Columns\TextColumn::make('user.name')
                ->label('User')
                ->description(fn(\Illuminate\Database\Eloquent\Model $record): string => $record->user?->email ?? '')
                ->searchable()
                ->sortable(),

            \Filament\Tables\Columns\IconColumn::make('enabled')
                ->label('Enabled')
                ->boolean(),

            \Filament\Tables\Columns\TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable(),
        ])
        ->filters([
            \Filament\Tables\Filters\TernaryFilter::make('enabled')
                ->label('Status')
                ->trueLabel('Active')
                ->falseLabel('Inactive'),
        ])
        ->actions([
            \Filament\Tables\Actions\ViewAction::make(),
            \Filament\Tables\Actions\EditAction::make(),
            \N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Actions\ResetPasswordAction::make(),
            \Filament\Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            \Filament\Tables\Actions\BulkActionGroup::make([
                \Filament\Tables\Actions\BulkAction::make('enable')
                    ->label('Enable selected')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn(\Illuminate\Support\Collection $records) => $records->each->update(['enabled' => true])),

                \Filament\Tables\Actions\BulkAction::make('disable')
                    ->label('Disable selected')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn(\Illuminate\Support\Collection $records) => $records->each->update(['enabled' => false])),

                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}
```

- [ ] **Step 4.4: Run tests to confirm pass**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php -v
```

Expected: All tests PASS.

- [ ] **Step 4.5: Commit**

```bash
git add src/Resources/WebDavAccountResource.php tests/Feature/Resources/WebDavAccountResourceTest.php
git commit -m "feat: implement WebDavAccountResource table schema with filters and bulk actions"
```

---

## Task 5: ResetPasswordAction

**Files:**
- Create: `src/Resources/WebDavAccountResource/Actions/ResetPasswordAction.php`
- Modify: `tests/Feature/Resources/WebDavAccountResourceTest.php` (add reset-password tests)

- [ ] **Step 5.1: Write failing reset-password tests**

Append to `tests/Feature/Resources/WebDavAccountResourceTest.php`:

```php
// ─── ResetPasswordAction ──────────────────────────────────────────────────────

it('resets the password via table action', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'reset-me',
        'password_encrypted' => Hash::make('old-password'),
        'enabled'            => true,
        'user_id'            => $user->id,
    ]);

    $oldHash = $account->password_encrypted;

    livewire(ListWebDavAccounts::class)
        ->callTableAction('resetPassword', $account, data: [
            'password'             => 'NewSecret1234!',
            'password_confirmation' => 'NewSecret1234!',
        ])
        ->assertHasNoTableActionErrors();

    $newHash = $account->fresh()->password_encrypted;
    expect($newHash)->not->toBe($oldHash);
    expect(\Illuminate\Support\Facades\Hash::check('NewSecret1234!', $newHash))->toBeTrue();
});
```

- [ ] **Step 5.2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php --filter="resets the password" -v
```

Expected: FAIL — `ResetPasswordAction` class does not exist.

- [ ] **Step 5.3: Implement ResetPasswordAction**

Create `src/Resources/WebDavAccountResource/Actions/ResetPasswordAction.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Actions;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountUpdateDto;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;

final class ResetPasswordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'resetPassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Reset Password')
            ->icon('heroicon-o-key')
            ->form([
                TextInput::make('password')
                    ->label('New Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->default(fn(): string => Str::password(16)),

                TextInput::make('password_confirmation')
                    ->label('Confirm New Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password'),
            ])
            ->action(function (Model $record, array $data): void {
                app(AccountManagementService::class)->update(
                    $record,
                    new AccountUpdateDto(password: $data['password']),
                );

                Notification::make()
                    ->success()
                    ->title('Password reset successfully')
                    ->send();
            });
    }
}
```

- [ ] **Step 5.4: Run tests to confirm pass**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php -v
```

Expected: All tests PASS.

- [ ] **Step 5.5: Commit**

```bash
git add src/Resources/WebDavAccountResource/Actions/ResetPasswordAction.php \
    tests/Feature/Resources/WebDavAccountResourceTest.php
git commit -m "feat: implement ResetPasswordAction with auto-generated password modal"
```

---

## Task 6: CreateWebDavAccount — service delegation

**Files:**
- Modify: `src/Resources/WebDavAccountResource/Pages/CreateWebDavAccount.php`
- Modify: `tests/Feature/Resources/WebDavAccountResourceTest.php` (add create tests)

- [ ] **Step 6.1: Write failing create tests**

Append to `tests/Feature/Resources/WebDavAccountResourceTest.php`:

```php
// ─── Create ───────────────────────────────────────────────────────────────────

it('creates a webdav account via the create form', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $targetUser = User::factory()->create();

    livewire(CreateWebDavAccount::class)
        ->fillForm([
            'username'             => 'brand-new-account',
            'display_name'         => 'Brand New',
            'password'             => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
            'user_id'              => $targetUser->id,
            'enabled'              => true,
            'meta'                 => null,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $account = WebDavAccountModel::where('username', 'brand-new-account')->firstOrFail();
    expect($account->display_name)->toBe('Brand New');
    expect($account->user_id)->toBe($targetUser->id);
    expect($account->enabled)->toBeTrue();
    expect(\Illuminate\Support\Facades\Hash::check('Secret1234!', $account->password_encrypted))->toBeTrue();
});

it('stores meta key-value pairs on create', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    livewire(CreateWebDavAccount::class)
        ->fillForm([
            'username'             => 'meta-account',
            'password'             => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
            'user_id'              => $user->id,
            'enabled'              => true,
            'meta'                 => ['quota' => '1GB', 'role' => 'editor'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $account = WebDavAccountModel::where('username', 'meta-account')->firstOrFail();
    expect($account->meta)->toBe(['quota' => '1GB', 'role' => 'editor']);
});

it('shows a validation error for duplicate username on create', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    WebDavAccountModel::create([
        'username'           => 'existing-account',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
    ]);

    livewire(CreateWebDavAccount::class)
        ->fillForm([
            'username'             => 'existing-account',
            'password'             => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
            'user_id'              => $user->id,
            'enabled'              => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['username']);
});
```

- [ ] **Step 6.2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php --filter="creates a webdav|stores meta|duplicate username on create" -v
```

Expected: FAIL — `handleRecordCreation` still calls `parent::`.

- [ ] **Step 6.3: Implement CreateWebDavAccount**

Replace the full content of `src/Resources/WebDavAccountResource/Pages/CreateWebDavAccount.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

final class CreateWebDavAccount extends CreateRecord
{
    protected static string $resource = WebDavAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $account = app(AccountManagementService::class)->create(
                username: $data['username'],
                password: $data['password'],
                displayName: $data['display_name'] ?? null,
                userId: $data['user_id'] ?? null,
                enabled: (bool) ($data['enabled'] ?? true),
            );
        } catch (DuplicateUsernameException $e) {
            throw ValidationException::withMessages([
                'data.username' => $e->getMessage(),
            ]);
        }

        if (!empty($data['meta'])) {
            $account->setAttribute('meta', $data['meta']);
            $account->save();
        }

        return $account;
    }
}
```

- [ ] **Step 6.4: Run tests to confirm pass**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php -v
```

Expected: All tests PASS.

- [ ] **Step 6.5: Commit**

```bash
git add src/Resources/WebDavAccountResource/Pages/CreateWebDavAccount.php \
    tests/Feature/Resources/WebDavAccountResourceTest.php
git commit -m "feat: implement CreateWebDavAccount with AccountManagementService delegation"
```

---

## Task 7: EditWebDavAccount — service delegation + header action

**Files:**
- Modify: `src/Resources/WebDavAccountResource/Pages/EditWebDavAccount.php`
- Modify: `tests/Feature/Resources/WebDavAccountResourceTest.php` (add edit + header reset tests)

- [ ] **Step 7.1: Write failing edit tests**

Append to `tests/Feature/Resources/WebDavAccountResourceTest.php`:

```php
// ─── Edit ─────────────────────────────────────────────────────────────────────

it('updates a webdav account without changing the password when password is left empty', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $oldHash = Hash::make('original-password');
    $account = WebDavAccountModel::create([
        'username'           => 'edit-me',
        'password_encrypted' => $oldHash,
        'enabled'            => true,
        'user_id'            => $user->id,
        'display_name'       => 'Old Name',
    ]);

    livewire(EditWebDavAccount::class, ['record' => $account->getKey()])
        ->fillForm([
            'username'             => 'edit-me',
            'display_name'         => 'New Name',
            'password'             => '',
            'password_confirmation' => '',
            'user_id'              => $user->id,
            'enabled'              => true,
            'meta'                 => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $account->fresh();
    expect($fresh->display_name)->toBe('New Name');
    expect($fresh->password_encrypted)->toBe($oldHash);
});

it('updates the password when a new password is provided on edit', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'change-password',
        'password_encrypted' => Hash::make('old-pass'),
        'enabled'            => true,
        'user_id'            => $user->id,
    ]);

    $oldHash = $account->password_encrypted;

    livewire(EditWebDavAccount::class, ['record' => $account->getKey()])
        ->fillForm([
            'username'             => 'change-password',
            'display_name'         => null,
            'password'             => 'BrandNew5678!',
            'password_confirmation' => 'BrandNew5678!',
            'user_id'              => $user->id,
            'enabled'              => true,
            'meta'                 => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $account->fresh();
    expect($fresh->password_encrypted)->not->toBe($oldHash);
    expect(Hash::check('BrandNew5678!', $fresh->password_encrypted))->toBeTrue();
});

it('shows a validation error for duplicate username on edit', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    WebDavAccountModel::create([
        'username'           => 'already-taken',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
    ]);

    $account = WebDavAccountModel::create([
        'username'           => 'edit-duplicate',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
    ]);

    livewire(EditWebDavAccount::class, ['record' => $account->getKey()])
        ->fillForm([
            'username'             => 'already-taken',
            'password'             => '',
            'password_confirmation' => '',
            'user_id'              => $user->id,
            'enabled'              => true,
        ])
        ->call('save')
        ->assertHasFormErrors(['username']);
});

it('updates meta on edit', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'meta-edit',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
        'meta'               => ['old' => 'value'],
    ]);

    livewire(EditWebDavAccount::class, ['record' => $account->getKey()])
        ->fillForm([
            'username'             => 'meta-edit',
            'password'             => '',
            'password_confirmation' => '',
            'user_id'              => $user->id,
            'enabled'              => true,
            'meta'                 => ['new' => 'data'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($account->fresh()->meta)->toBe(['new' => 'data']);
});

it('resets the password via the edit page header action', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'header-reset',
        'password_encrypted' => Hash::make('old-pass'),
        'enabled'            => true,
        'user_id'            => $user->id,
    ]);

    $oldHash = $account->password_encrypted;

    livewire(EditWebDavAccount::class, ['record' => $account->getKey()])
        ->callAction('resetPassword', data: [
            'password'             => 'HeaderNew5678!',
            'password_confirmation' => 'HeaderNew5678!',
        ])
        ->assertHasNoActionErrors();

    $fresh = $account->fresh();
    expect($fresh->password_encrypted)->not->toBe($oldHash);
    expect(Hash::check('HeaderNew5678!', $fresh->password_encrypted))->toBeTrue();
});
```

- [ ] **Step 7.2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php --filter="updates a webdav|updates the password|duplicate username on edit|updates meta|header action" -v
```

Expected: FAIL — `handleRecordUpdate` still calls `parent::`.

- [ ] **Step 7.3: Implement EditWebDavAccount**

Replace the full content of `src/Resources/WebDavAccountResource/Pages/EditWebDavAccount.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountUpdateDto;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Actions\ResetPasswordAction;

final class EditWebDavAccount extends EditRecord
{
    protected static string $resource = WebDavAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            \Filament\Actions\Action::make('resetPassword')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->form([
                    TextInput::make('password')
                        ->label('New Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->default(fn(): string => Str::password(16)),

                    TextInput::make('password_confirmation')
                        ->label('Confirm New Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('password'),
                ])
                ->action(function (array $data): void {
                    app(AccountManagementService::class)->update(
                        $this->getRecord(),
                        new AccountUpdateDto(password: $data['password']),
                    );

                    Notification::make()
                        ->success()
                        ->title('Password reset successfully')
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $currentUsername = (string) $record->getAttribute('username');
        $newUsername = $data['username'] ?? $currentUsername;
        $password = ($data['password'] ?? '') !== '' ? $data['password'] : null;

        $displayName = ($data['display_name'] ?? '') !== '' ? $data['display_name'] : null;
        $clearDisplayName = ($data['display_name'] ?? null) === null || ($data['display_name'] ?? '') === '';

        try {
            app(AccountManagementService::class)->update(
                $record,
                new AccountUpdateDto(
                    newUsername: $newUsername !== $currentUsername ? $newUsername : null,
                    password: $password,
                    displayName: $displayName,
                    clearDisplayName: $clearDisplayName,
                    userId: $data['user_id'] ?? null,
                    enabled: (bool) ($data['enabled'] ?? true),
                ),
            );
        } catch (DuplicateUsernameException $e) {
            throw ValidationException::withMessages([
                'data.username' => $e->getMessage(),
            ]);
        }

        $record->setAttribute('meta', ($data['meta'] ?? null) ?: null);
        $record->save();

        return $record->refresh();
    }
}
```

- [ ] **Step 7.4: Run tests to confirm pass**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php -v
```

Expected: All tests PASS.

- [ ] **Step 7.5: Commit**

```bash
git add src/Resources/WebDavAccountResource/Pages/EditWebDavAccount.php \
    tests/Feature/Resources/WebDavAccountResourceTest.php
git commit -m "feat: implement EditWebDavAccount with service delegation and reset password header action"
```

---

## Task 8: ViewWebDavAccount

**Files:**
- Modify: `src/Resources/WebDavAccountResource/Pages/ViewWebDavAccount.php` (already stubbed — finalize)
- Modify: `tests/Feature/Resources/WebDavAccountResourceTest.php` (add view tests)

- [ ] **Step 8.1: Write failing view test**

Append to `tests/Feature/Resources/WebDavAccountResourceTest.php`:

```php
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages\ViewWebDavAccount;

// ─── View ─────────────────────────────────────────────────────────────────────

it('renders the view page with account data', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = WebDavAccountModel::create([
        'username'           => 'view-me',
        'password_encrypted' => Hash::make('secret'),
        'enabled'            => true,
        'user_id'            => $user->id,
        'display_name'       => 'View Me',
    ]);

    livewire(ViewWebDavAccount::class, ['record' => $account->getKey()])
        ->assertFormFieldExists('username')
        ->assertFormFieldExists('display_name')
        ->assertFormFieldExists('enabled');
});
```

- [ ] **Step 8.2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Feature/Resources/WebDavAccountResourceTest.php --filter="renders the view page" -v
```

Expected: FAIL (or PASS if Filament already handles it — check output).

- [ ] **Step 8.3: Finalize ViewWebDavAccount if needed**

The stub from Task 2 is already complete. If the test from Step 8.2 fails for a reason other than a missing class, investigate. Otherwise no changes needed here.

- [ ] **Step 8.4: Run full test suite**

```bash
./vendor/bin/pest -v
```

Expected: All tests PASS.

- [ ] **Step 8.5: Run code style fix**

```bash
composer lint
```

Fix any style issues reported.

- [ ] **Step 8.6: Commit**

```bash
git add tests/Feature/Resources/WebDavAccountResourceTest.php
git commit -m "feat: add ViewWebDavAccount test coverage and finalize resource implementation"
```

---

## Self-Review Checklist

After completing all tasks, verify against the spec:

| Spec requirement | Covered by |
|-----------------|-----------|
| List page with columns (username, display_name, user, enabled, created_at) | Task 4 |
| TernaryFilter for enabled | Task 4 |
| Row actions: View, Edit, ResetPassword, Delete | Task 4 |
| Bulk: Enable, Disable, Delete | Task 4 |
| Create form with all fields | Task 3 |
| password auto-generate button | Task 3 |
| user_id required | Task 3 |
| meta KeyValue editor | Task 3 |
| Edit form — password optional (empty = no change) | Task 7 |
| Edit page header ResetPasswordAction | Task 7 |
| View page (read-only) | Task 8 |
| Plugin `withoutAccountResource()` | Task 1 |
| Plugin `userSelectUsing()` | Task 1 |
| Plugin auto-registers resource by default | Task 1 |
| DuplicateUsernameException → form field error | Task 6, 7 |
| Password always hashed via service | Task 6, 7 |
| ResetPasswordAction modal with auto-generated password | Task 5 |
| Meta handled separately via Eloquent (service doesn't support it) | Task 6, 7 |
