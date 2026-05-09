<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;
use N3XT0R\LaravelWebdavServerFilament\Concerns\NotifiesAccountCreation;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountCreatedEvent;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

final class CreateWebDavAccount extends CreateRecord
{
    use NotifiesAccountCreation;

    protected static string $resource = WebDavAccountResource::class;

    /**
     * Create a WebDAV account through the package management service.
     *
     * @param  array<string, mixed>  $data  Validated form data for the new account.
     * @return Model Newly persisted account model.
     *
     * @throws ValidationException When the requested username is already in use.
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            $account = app(AccountManagementService::class)->create(
                username: $data['username'],
                password: $data['password'],
                displayName: $data['display_name'] ?? null,
                userId: $data['user_id'] ?? null,
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
