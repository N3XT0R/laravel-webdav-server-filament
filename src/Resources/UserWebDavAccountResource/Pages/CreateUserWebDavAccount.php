<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages;

use Carbon\CarbonInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountCreatedEvent;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Notifications\WebDavAccountCreatedNotification;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource;
use Throwable;

final class CreateUserWebDavAccount extends CreateRecord
{
    protected static string $resource = UserWebDavAccountResource::class;

    /**
     * Abort with 403 when the user resource is not enabled for the authenticated user on this panel.
     */
    public function mount(): void
    {
        try {
            $callback = LaravelWebdavServerFilamentPlugin::get()->getUserAccountResourceCallback();
        } catch (Throwable) {
            abort(403);
        }

        abort_unless($callback !== null && $callback(auth()->user()), 403);

        parent::mount();
    }

    /**
     * Create a WebDAV account through the package management service, linked to the authenticated user.
     *
     * @param  array<string, mixed>  $data  Validated form data for the new account.
     * @return Model Newly persisted account model.
     *
     * @throws ValidationException When the requested username is already in use.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['user_id'] = auth()->id();

        try {
            $account = app(AccountManagementService::class)->create(
                username: $data['username'],
                password: $data['password'],
                displayName: $data['display_name'] ?? null,
                userId: $data['user_id'],
                enabled: (bool)($data['enabled'] ?? true),
            );
        } catch (DuplicateUsernameException $exception) {
            throw ValidationException::withMessages([
                'data.username' => $exception->getMessage(),
            ]);
        }

        $account->setAttribute('meta', ($data['meta'] ?? null) ?: null);
        $account->save();

        new WebDavAccountCreatedEvent($account)->dispatchForListeners();

        $this->notifyLinkedUser($account, (string)$data['password']);

        return $account;
    }

    private function notifyLinkedUser(Model $record, string $password): void
    {
        if (!(bool)config('laravel-webdav-server-filament.notifications.enabled', true)) {
            return;
        }

        $notifiable = $record->getAttribute('user') ?? $record->user;

        if (!is_object($notifiable) || !method_exists($notifiable, 'notify')) {
            return;
        }

        $createdAt = $record->getAttribute('created_at');

        $notifiable->notify(new WebDavAccountCreatedNotification(
            username: (string)$record->getAttribute('username'),
            password: $password,
            createdAt: $createdAt instanceof CarbonInterface ? $createdAt : now(),
            userName: $this->readNotifiableString($notifiable, 'name'),
            userEmail: $this->readNotifiableString($notifiable, 'email'),
        ));
    }

    private function readNotifiableString(object $notifiable, string $key): ?string
    {
        $value = method_exists($notifiable, 'getAttribute')
            ? $notifiable->getAttribute($key)
            : ($notifiable->{$key} ?? null);

        return is_scalar($value) ? (string)$value : null;
    }
}
