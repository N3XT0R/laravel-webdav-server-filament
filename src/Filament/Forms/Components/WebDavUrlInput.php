<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServer\Facades\WebDavPath;

final class WebDavUrlInput extends TextInput
{
    /**
     * Configure the field as a read-only, copyable WebDAV URL display for the current record.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.webdav_url'))
            ->readOnly()
            ->dehydrated(false)
            ->afterStateHydrated(function (WebDavUrlInput $component, ?Model $record): void {
                $component->state($record === null ? null : $component->resolveWebDavUrl($record));
            })
            ->copyable(copyMessage: __('webdav-server-filament::webdav-server-filament.resources.accounts.notifications.webdav_url_copied'))
            ->columnSpanFull();
    }

    private function resolveWebDavUrl(Model $record): string
    {
        $spaceKey = (string) config('webdav-server.storage.default_space', 'default');
        $principalIdColumn = (string) config('webdav-server.auth.user_id_column', 'id');
        $principalId = (string) $record->getAttribute($principalIdColumn);

        return rtrim(WebDavPath::resolveUrl($spaceKey), '/') . '/' . rawurlencode($principalId);
    }
}
