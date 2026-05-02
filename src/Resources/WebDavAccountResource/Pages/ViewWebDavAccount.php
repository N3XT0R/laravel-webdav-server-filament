<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;

use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\Facades\WebDavPath;
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

            TextInput::make('webdav_url')
                ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.webdav_url'))
                ->readOnly()
                ->dehydrated(false)
                ->afterStateHydrated(function (TextInput $component): void {
                    $component->state($this->resolveWebDavUrl($this->getRecord()));
                })
                ->copyable(copyMessage: __('webdav-server-filament::webdav-server-filament.resources.accounts.notifications.webdav_url_copied'))
                ->columnSpanFull(),
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

    private function resolveWebDavUrl(Model $record): string
    {
        $spaceKey = (string) config('webdav-server.storage.default_space', 'default');
        $principalIdColumn = (string) config('webdav-server.auth.user_id_column', 'id');
        $principalId = (string) $record->getAttribute($principalIdColumn);

        return rtrim(WebDavPath::resolveUrl($spaceKey), '/') . '/' . rawurlencode($principalId);
    }
}
