<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Tests\Feature\Resources;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use N3XT0R\LaravelWebdavServer\Facades\WebDavPath;
use N3XT0R\LaravelWebdavServerFilament\Filament\Forms\Components\WebDavUrlInput;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages\CreateWebDavAccount;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages\EditWebDavAccount;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages\ListWebDavAccounts;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages\ViewWebDavAccount;
use N3XT0R\LaravelWebdavServerFilament\Tests\DatabaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use Workbench\App\Models\User;

final class WebDavAccountResourceTest extends DatabaseTestCase
{
    #[Test]
    public function it_renders_all_expected_fields_on_the_create_form(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateWebDavAccount::class)
            ->assertFormFieldExists('username')
            ->assertFormFieldExists('display_name')
            ->assertFormFieldExists('password')
            ->assertFormFieldExists('password_confirmation')
            ->assertFormFieldExists('user_id')
            ->assertFormFieldExists('enabled')
            ->assertFormFieldExists('meta');
    }

    #[Test]
    public function it_renders_all_expected_fields_on_the_edit_form(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'edit-fields']);

        Livewire::test(EditWebDavAccount::class, ['record' => $account->getKey()])
            ->assertFormFieldExists('username')
            ->assertFormFieldExists('display_name')
            ->assertFormFieldExists('password')
            ->assertFormFieldExists('password_confirmation')
            ->assertFormFieldExists('user_id')
            ->assertFormFieldExists('enabled')
            ->assertFormFieldExists('meta');
    }

    #[Test]
    public function it_validates_required_create_fields(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateWebDavAccount::class)
            ->fillForm([
                'username' => '',
                'password' => '',
                'password_confirmation' => '',
                'user_id' => null,
                'enabled' => true,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'username' => 'required',
                'password' => 'required',
                'password_confirmation' => 'required',
                'user_id' => 'required',
            ]);
    }

    #[Test]
    public function it_requires_password_confirmation_to_match_password_on_create(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateWebDavAccount::class)
            ->fillForm([
                'username' => 'new-account',
                'password' => 'Secret1234!',
                'password_confirmation' => 'Different1234!',
                'user_id' => $user->id,
                'enabled' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['password_confirmation']);
    }

    #[Test]
    public function it_lists_webdav_accounts_in_the_table(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'listed-user']);

        Livewire::test(ListWebDavAccounts::class)
            ->assertCanSeeTableRecords([$account]);
    }

    #[Test]
    public function it_can_filter_accounts_by_enabled_state(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $active = $this->createAccount($user, ['username' => 'active-user', 'enabled' => true]);
        $inactive = $this->createAccount($user, ['username' => 'inactive-user', 'enabled' => false]);

        Livewire::test(ListWebDavAccounts::class)
            ->filterTable('enabled', true)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    #[Test]
    public function it_can_bulk_enable_selected_accounts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'disabled-user', 'enabled' => false]);

        Livewire::test(ListWebDavAccounts::class)
            ->callTableBulkAction('enable', [$account]);

        self::assertTrue($account->fresh()->enabled);
    }

    #[Test]
    public function it_can_bulk_disable_selected_accounts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'active-bulk-user', 'enabled' => true]);

        Livewire::test(ListWebDavAccounts::class)
            ->callTableBulkAction('disable', [$account]);

        self::assertFalse($account->fresh()->enabled);
    }

    #[Test]
    public function it_can_delete_an_account_from_the_table(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'to-delete']);

        Livewire::test(ListWebDavAccounts::class)
            ->callTableAction('delete', $account);

        $this->assertModelMissing($account);
    }

    #[Test]
    public function it_resets_the_password_via_table_action(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'reset-me']);
        $oldHash = $account->password_encrypted;

        Livewire::test(ListWebDavAccounts::class)
            ->callTableAction('resetPassword', $account, data: [
                'password' => 'NewSecret1234!',
                'password_confirmation' => 'NewSecret1234!',
            ])
            ->assertHasNoTableActionErrors();

        $newHash = $account->fresh()->password_encrypted;

        self::assertNotSame($oldHash, $newHash);
        self::assertTrue(Hash::check('NewSecret1234!', $newHash));
    }

    #[Test]
    public function it_creates_a_webdav_account_via_the_create_form(): void
    {
        $actingUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $this->actingAs($actingUser);

        Livewire::test(CreateWebDavAccount::class)
            ->fillForm([
                'username' => 'brand-new-account',
                'display_name' => 'Brand New',
                'password' => 'Secret1234!',
                'password_confirmation' => 'Secret1234!',
                'user_id' => $targetUser->id,
                'enabled' => true,
                'meta' => ['quota' => '1GB'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $account = WebDavAccountModel::where('username', 'brand-new-account')->firstOrFail();

        self::assertSame('Brand New', $account->display_name);
        self::assertSame($targetUser->id, $account->user_id);
        self::assertTrue($account->enabled);
        self::assertSame(['quota' => '1GB'], $account->meta);
        self::assertTrue(Hash::check('Secret1234!', $account->password_encrypted));
    }

    #[Test]
    public function it_shows_a_validation_error_for_duplicate_username_on_create(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createAccount($user, ['username' => 'existing-account']);

        Livewire::test(CreateWebDavAccount::class)
            ->fillForm([
                'username' => 'existing-account',
                'password' => 'Secret1234!',
                'password_confirmation' => 'Secret1234!',
                'user_id' => $user->id,
                'enabled' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['username']);
    }

    #[Test]
    public function it_updates_a_webdav_account_without_changing_the_password_when_password_is_left_empty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $oldHash = Hash::make('original-password');
        $account = $this->createAccount($user, [
            'username' => 'edit-me',
            'password_encrypted' => $oldHash,
            'display_name' => 'Old Name',
        ]);

        Livewire::test(EditWebDavAccount::class, ['record' => $account->getKey()])
            ->fillForm([
                'username' => 'edit-me',
                'display_name' => 'New Name',
                'password' => '',
                'password_confirmation' => '',
                'user_id' => $user->id,
                'enabled' => true,
                'meta' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $account->fresh();

        self::assertSame('New Name', $fresh->display_name);
        self::assertSame($oldHash, $fresh->password_encrypted);
    }

    #[Test]
    public function it_updates_the_password_when_a_new_password_is_provided_on_edit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'change-password']);
        $oldHash = $account->password_encrypted;

        Livewire::test(EditWebDavAccount::class, ['record' => $account->getKey()])
            ->fillForm([
                'username' => 'change-password',
                'display_name' => null,
                'password' => 'BrandNew5678!',
                'password_confirmation' => 'BrandNew5678!',
                'user_id' => $user->id,
                'enabled' => true,
                'meta' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $account->fresh();

        self::assertNotSame($oldHash, $fresh->password_encrypted);
        self::assertTrue(Hash::check('BrandNew5678!', $fresh->password_encrypted));
    }

    #[Test]
    public function it_shows_a_validation_error_for_duplicate_username_on_edit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createAccount($user, ['username' => 'already-taken']);
        $account = $this->createAccount($user, ['username' => 'edit-duplicate']);

        Livewire::test(EditWebDavAccount::class, ['record' => $account->getKey()])
            ->fillForm([
                'username' => 'already-taken',
                'password' => '',
                'password_confirmation' => '',
                'user_id' => $user->id,
                'enabled' => true,
            ])
            ->call('save')
            ->assertHasFormErrors(['username']);
    }

    #[Test]
    public function it_updates_meta_on_edit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, [
            'username' => 'meta-edit',
            'meta' => ['old' => 'value'],
        ]);

        Livewire::test(EditWebDavAccount::class, ['record' => $account->getKey()])
            ->fillForm([
                'username' => 'meta-edit',
                'password' => '',
                'password_confirmation' => '',
                'user_id' => $user->id,
                'enabled' => true,
                'meta' => ['new' => 'data'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        self::assertSame(['new' => 'data'], $account->fresh()->meta);
    }

    #[Test]
    public function it_resets_the_password_via_the_edit_page_header_action(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'header-reset']);
        $oldHash = $account->password_encrypted;

        Livewire::test(EditWebDavAccount::class, ['record' => $account->getKey()])
            ->callAction('resetPassword', data: [
                'password' => 'HeaderNew5678!',
                'password_confirmation' => 'HeaderNew5678!',
            ])
            ->assertHasNoActionErrors();

        $fresh = $account->fresh();

        self::assertNotSame($oldHash, $fresh->password_encrypted);
        self::assertTrue(Hash::check('HeaderNew5678!', $fresh->password_encrypted));
    }

    #[Test]
    public function it_renders_the_view_page_with_account_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, [
            'username' => 'view-me',
            'display_name' => 'View Me',
        ]);
        $webDavUrl = rtrim(WebDavPath::resolveUrl('default'), '/') . '/' . $account->user_id;

        Livewire::test(ViewWebDavAccount::class, ['record' => $account->getKey()])
            ->assertFormFieldExists('username')
            ->assertFormFieldExists('display_name')
            ->assertFormFieldExists('webdav_url', function (WebDavUrlInput $field): bool {
                self::assertTrue($field->isReadOnly());
                self::assertTrue($field->isCopyable());

                return true;
            })
            ->assertFormSet([
                'webdav_url' => $webDavUrl,
            ])
            ->assertSee('WebDAV URL copied')
            ->assertFormFieldExists('enabled');
    }

    /**
     * Create a persisted WebDAV account for resource feature tests.
     *
     * @param  User  $user  User model linked to the account.
     * @param  array<string, mixed>  $attributes  Account attributes overriding defaults.
     * @return WebDavAccountModel Persisted WebDAV account model.
     */
    private function createAccount(User $user, array $attributes = []): WebDavAccountModel
    {
        return WebDavAccountModel::create([
            'username' => $attributes['username'] ?? 'account-' . Str::uuid()->toString(),
            'password_encrypted' => $attributes['password_encrypted'] ?? Hash::make('secret'),
            'enabled' => $attributes['enabled'] ?? true,
            'user_id' => $attributes['user_id'] ?? $user->id,
            'display_name' => $attributes['display_name'] ?? null,
            'meta' => $attributes['meta'] ?? null,
        ]);
    }
}
