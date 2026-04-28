<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use N3XT0R\LaravelWebdavServer\DTO\Management\AccountUpdateDto;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;

final class ResetPasswordAction extends Action
{
    /**
     * Return the default action name used by table and page tests.
     *
     * @return string|null Default action name.
     */
    public static function getDefaultName(): ?string
    {
        return 'resetPassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.actions.reset_password'))
            ->icon('heroicon-o-key')
            ->schema([
                TextInput::make('password')
                    ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.new_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->default(fn (): string => Str::password(16)),

                TextInput::make('password_confirmation')
                    ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.new_password_confirmation'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password'),
            ])
            ->action(function (Model $record, array $data): void {
                app(AccountManagementService::class)->update(
                    $record,
                    new AccountUpdateDto(password: $data['password']),
                );

                Notification::make()
                    ->success()
                    ->title(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.notifications.password_reset'))
                    ->send();
            });
    }
}
