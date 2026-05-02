<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource;
use Throwable;

final class ListUserWebDavAccounts extends ListRecords
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
     * Return page header actions for the account list.
     *
     * @return array<int, CreateAction> Actions shown above the table.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
