<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use N3XT0R\LaravelWebdavServerFilament\Filament\Forms\Components\WebDavUrlInput;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

final class ViewWebDavAccount extends ViewRecord
{
    protected static string $resource = WebDavAccountResource::class;

    /**
     * Extend the resource view form with a copyable WebDAV URL for the displayed account.
     *
     * @param  Schema  $schema  Filament schema instance being configured for the view page.
     * @return Schema Configured view form schema with the additional WebDAV URL field.
     */
    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        return $schema->schema([
            ...$schema->getComponents(withHidden: true),

            WebDavUrlInput::make('webdav_url'),
        ]);
    }

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
