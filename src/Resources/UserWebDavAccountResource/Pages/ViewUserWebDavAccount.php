<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use N3XT0R\LaravelWebdavServerFilament\Filament\Forms\Components\WebDavUrlInput;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource;
use Throwable;

final class ViewUserWebDavAccount extends ViewRecord
{
    protected static string $resource = UserWebDavAccountResource::class;

    /**
     * Abort with 403 when the user resource is not enabled for the authenticated user on this panel.
     *
     * @param  int|string  $record  Record key being viewed.
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
