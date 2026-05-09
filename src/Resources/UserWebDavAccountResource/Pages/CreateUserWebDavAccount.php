<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;
use N3XT0R\LaravelWebdavServerFilament\Concerns\NotifiesAccountCreation;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountCreatedEvent;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource;
use Throwable;

final class CreateUserWebDavAccount extends CreateRecord
{
    use NotifiesAccountCreation;

    protected static string $resource = UserWebDavAccountResource::class;

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return __('webdav-server-filament::webdav-server-filament.resources.accounts.pages.create.description');
    }

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

}
