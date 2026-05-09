<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Tests\Feature\Resources;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages\CreateUserWebDavAccount;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages\EditUserWebDavAccount;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages\ListUserWebDavAccounts;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages\ViewUserWebDavAccount;
use N3XT0R\LaravelWebdavServerFilament\Tests\DatabaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use Workbench\App\Models\User;

final class UserWebDavAccountResourceTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);

        parent::tearDown();
    }

    #[Test]
    public function it_renders_expected_fields_on_the_create_form(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateUserWebDavAccount::class)
            ->assertFormFieldExists('username')
            ->assertFormFieldExists('display_name')
            ->assertFormFieldExists('password')
            ->assertFormFieldExists('password_confirmation')
            ->assertFormFieldExists('enabled')
            ->assertFormFieldExists('meta')
            ->assertFormFieldDoesNotExist('user_id');
    }

    #[Test]
    public function it_creates_an_account_linked_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateUserWebDavAccount::class)
            ->fillForm([
                'username' => 'self-service-account',
                'password' => 'ValidP@ssword123',
                'password_confirmation' => 'ValidP@ssword123',
                'enabled' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $account = WebDavAccountModel::where('username', 'self-service-account')->firstOrFail();

        self::assertSame($user->id, $account->user_id);
    }

    #[Test]
    public function it_lists_only_accounts_belonging_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);

        $own = $this->createAccount($user, ['username' => 'my-account']);
        $this->createAccount($other, ['username' => 'their-account']);

        Livewire::test(ListUserWebDavAccounts::class)
            ->assertCanSeeTableRecords([$own]);
    }

    #[Test]
    public function it_does_not_show_accounts_from_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);

        $foreign = $this->createAccount($other, ['username' => 'foreign-account']);

        Livewire::test(ListUserWebDavAccounts::class)
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    #[Test]
    public function it_can_edit_an_own_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'editable-account', 'display_name' => 'Old Name']);

        Livewire::test(EditUserWebDavAccount::class, ['record' => $account->getKey()])
            ->fillForm([
                'username' => 'editable-account',
                'display_name' => 'New Name',
                'password' => '',
                'password_confirmation' => '',
                'enabled' => true,
                'meta' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        self::assertSame('New Name', $account->fresh()->display_name);
    }

    #[Test]
    public function it_renders_the_view_page_for_an_own_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'view-me']);

        Livewire::test(ViewUserWebDavAccount::class, ['record' => $account->getKey()])
            ->assertFormFieldExists('username')
            ->assertFormFieldExists('enabled');
    }

    #[Test]
    public function it_hides_meta_field_when_show_meta_is_disabled_in_config(): void
    {
        Config::set('laravel-webdav-server-filament.user_resource.show_meta', false);

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateUserWebDavAccount::class)
            ->assertFormFieldDoesNotExist('meta');
    }

    #[Test]
    public function it_denies_access_when_the_user_resource_callback_is_not_configured(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListUserWebDavAccounts::class)
            ->assertForbidden();
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
