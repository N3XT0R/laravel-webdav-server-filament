<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountUpdateDto;
use N3XT0R\LaravelWebdavServer\Exception\Auth\DuplicateUsernameException;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountDeletedEvent;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountUpdatedEvent;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource;
use Throwable;

final class EditUserWebDavAccount extends EditRecord
{
    protected static string $resource = UserWebDavAccountResource::class;

    /**
     * Abort with 403 when the user resource is not enabled for the authenticated user on this panel.
     *
     * @param  int|string  $record  Record key being edited.
     */
    public function mount(int|string $record): void
    {
        try {
            $callback = LaravelWebdavServerFilamentPlugin::get()->getUserAccountResourceCallback();
        } catch (Throwable) {
            abort(403);
        }

        abort_unless($callback !== null && $callback(auth()->user()), 403);

        parent::mount($record);
    }

    /**
     * Return page header actions for editing an account.
     *
     * @return array<int, ViewAction|DeleteAction> Actions shown above the edit form.
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(fn (Model $record): mixed => (new WebDavAccountDeletedEvent($record))->dispatchForListeners()),
        ];
    }

    /**
     * Update a WebDAV account through the package management service.
     *
     * @param  Model  $record  Account model being edited.
     * @param  array<string, mixed>  $data  Validated form data for the account update.
     * @return Model Refreshed account model.
     *
     * @throws ValidationException When the requested username is already in use.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $currentUsername = (string) $record->getAttribute('username');
        $newUsername = (string) ($data['username'] ?? $currentUsername);
        $password = filled($data['password'] ?? null) ? (string) $data['password'] : null;
        $displayName = filled($data['display_name'] ?? null) ? (string) $data['display_name'] : null;

        try {
            app(AccountManagementService::class)->update(
                $record,
                new AccountUpdateDto(
                    newUsername: $newUsername !== $currentUsername ? $newUsername : null,
                    password: $password,
                    displayName: $displayName,
                    clearDisplayName: blank($data['display_name'] ?? null),
                    userId: null,
                    clearUserId: false,
                    enabled: (bool) ($data['enabled'] ?? true),
                ),
            );
        } catch (DuplicateUsernameException $exception) {
            throw ValidationException::withMessages([
                'data.username' => $exception->getMessage(),
            ]);
        }

        $record->setAttribute('meta', ($data['meta'] ?? null) ?: null);
        $record->save();

        $record = $record->refresh();

        (new WebDavAccountUpdatedEvent($record))->dispatchForListeners();

        return $record;
    }
}
