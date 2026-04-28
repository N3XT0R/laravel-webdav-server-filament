<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

final class ViewWebDavAccount extends ViewRecord
{
    protected static string $resource = WebDavAccountResource::class;

    /**
     * Return page header actions for viewing an account.
     *
     * @return array<int, EditAction> Actions shown above the read-only account form.
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
