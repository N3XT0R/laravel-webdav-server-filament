<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

final class ListWebDavAccounts extends ListRecords
{
    protected static string $resource = WebDavAccountResource::class;

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
