<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Tests\Feature\Resources;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use N3XT0R\LaravelWebdavServer\Facades\WebDavPath;
use N3XT0R\LaravelWebdavServerFilament\Filament\Forms\Components\WebDavUrlInput;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountCreatedEvent;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountDeletedEvent;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountEvent;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountUpdatedEvent;
use N3XT0R\LaravelWebdavServerFilament\Notifications\WebDavAccountCreatedNotification;
use N3XT0R\LaravelWebdavServerFilament\Notifications\WebDavAccountPasswordResetNotification;
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
            ->assertFormFieldDisabled('user_id')
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
        Event::fake([WebDavAccountDeletedEvent::class, WebDavAccountEvent::class]);
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'to-delete']);
        $accountKey = $account->getKey();

        Livewire::test(ListWebDavAccounts::class)
            ->callTableAction('delete', $account);

        $this->assertModelMissing($account);
        $this->assertWebDavAccountEventDispatched(
            WebDavAccountDeletedEvent::class,
            WebDavAccountDeletedEvent::ACTION,
            $accountKey,
        );
    }

    #[Test]
    public function it_allows_listeners_to_subscribe_to_the_base_webdav_account_event(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user, ['username' => 'base-listener']);
        $capturedEvent = null;

        Event::listen(WebDavAccountEvent::class, function (WebDavAccountEvent $event) use (&$capturedEvent): void {
            $capturedEvent = $event;
        });

        (new WebDavAccountUpdatedEvent($account))->dispatchForListeners();

        self::assertInstanceOf(WebDavAccountUpdatedEvent::class, $capturedEvent);
        self::assertSame(WebDavAccountUpdatedEvent::ACTION, $capturedEvent->action);
        self::assertTrue($account->is($capturedEvent->record));
    }

    #[Test]
    public function it_resets_the_password_via_table_action(): void
    {
        Notification::fake();
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

        Notification::assertSentTo(
            $user,
            WebDavAccountPasswordResetNotification::class,
            function (WebDavAccountPasswordResetNotification $notification, array $channels) use ($user): bool {
                $mail = $notification->toMail($user);

                self::assertSame('Your WebDAV password was reset', $mail->subject);
                self::assertContains('The password for your WebDAV account "reset-me" was reset.', $mail->introLines);
                self::assertContains('New password: NewSecret1234!', $mail->introLines);

                return $channels === ['mail']
                    && $notification->getUsername() === 'reset-me'
                    && $notification->getPassword() === 'NewSecret1234!';
            },
        );
    }

    #[Test]
    public function it_does_not_notify_the_user_after_password_reset_when_notifications_are_disabled(): void
    {
        Notification::fake();
        config()->set('laravel-webdav-server-filament.notifications.enabled', false);
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->createAccount($user, ['username' => 'silent-reset']);

        Livewire::test(ListWebDavAccounts::class)
            ->callTableAction('resetPassword', $account, data: [
                'password' => 'SilentSecret1234!',
                'password_confirmation' => 'SilentSecret1234!',
            ])
            ->assertHasNoTableActionErrors();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_creates_a_webdav_account_via_the_create_form(): void
    {
        Event::fake([WebDavAccountCreatedEvent::class, WebDavAccountEvent::class]);
        Notification::fake();
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
        $this->assertWebDavAccountEventDispatched(
            WebDavAccountCreatedEvent::class,
            WebDavAccountCreatedEvent::ACTION,
            $account->getKey(),
        );

        Notification::assertSentTo(
            $targetUser,
            WebDavAccountCreatedNotification::class,
            function (WebDavAccountCreatedNotification $notification, array $channels) use ($account, $targetUser): bool {
                $mail = $notification->toMail($targetUser);

                self::assertSame('Your WebDAV account was created', $mail->subject);
                self::assertContains('WebDAV account: brand-new-account', $mail->introLines);
                self::assertContains("Linked user: {$targetUser->name} <{$targetUser->email}>", $mail->introLines);
                self::assertContains('Created at: ' . $account->created_at->toDateTimeString(), $mail->introLines);
                self::assertContains('Password: Secret1234!', $mail->introLines);

                return $channels === ['mail']
                    && $notification->getUsername() === 'brand-new-account'
                    && $notification->getPassword() === 'Secret1234!'
                    && $notification->getCreatedAt()->equalTo($account->created_at);
            },
        );
    }

    #[Test]
    public function it_does_not_notify_the_user_after_account_creation_when_notifications_are_disabled(): void
    {
        Notification::fake();
        config()->set('laravel-webdav-server-filament.notifications.enabled', false);
        $actingUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $this->actingAs($actingUser);

        Livewire::test(CreateWebDavAccount::class)
            ->fillForm([
                'username' => 'silent-account',
                'display_name' => 'Silent Account',
                'password' => 'Secret1234!',
                'password_confirmation' => 'Secret1234!',
                'user_id' => $targetUser->id,
                'enabled' => true,
                'meta' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Notification::assertNothingSent();
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
        Event::fake([WebDavAccountUpdatedEvent::class, WebDavAccountEvent::class]);
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
        $this->assertWebDavAccountEventDispatched(
            WebDavAccountUpdatedEvent::class,
            WebDavAccountUpdatedEvent::ACTION,
            $fresh->getKey(),
        );
    }

    #[Test]
    public function it_does_not_allow_changing_the_linked_user_on_edit(): void
    {
        $originalUser = User::factory()->create();
        $newUser = User::factory()->create();
        $this->actingAs($originalUser);
        $account = $this->createAccount($originalUser, [
            'username' => 'locked-user',
            'display_name' => 'Locked User',
        ]);

        Livewire::test(EditWebDavAccount::class, ['record' => $account->getKey()])
            ->fillForm([
                'username' => 'locked-user',
                'display_name' => 'Locked User Updated',
                'password' => '',
                'password_confirmation' => '',
                'user_id' => $newUser->id,
                'enabled' => true,
                'meta' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $account->fresh();

        self::assertSame($originalUser->id, $fresh->user_id);
        self::assertSame('Locked User Updated', $fresh->display_name);
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
     * Assert that a concrete WebDAV account event and the generic base event channel were dispatched.
     *
     * @param  class-string<WebDavAccountEvent>  $eventClass  Concrete lifecycle event class expected.
     * @param  string  $action  Expected lifecycle action value.
     * @param  int|string  $recordKey  Expected account record key.
     */
    private function assertWebDavAccountEventDispatched(string $eventClass, string $action, int|string $recordKey): void
    {
        Event::assertDispatched(
            $eventClass,
            fn (WebDavAccountEvent $event): bool => $event->action === $action
                && $event->record->getKey() === $recordKey,
        );

        Event::assertDispatched(
            WebDavAccountEvent::class,
            fn (string $eventName, array $payload): bool => $eventName === WebDavAccountEvent::class
                && ($event = $payload[0] ?? null) instanceof $eventClass
                && $event->action === $action
                && $event->record->getKey() === $recordKey,
        );
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
