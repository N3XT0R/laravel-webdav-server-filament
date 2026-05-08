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
use N3XT0R\LaravelWebdavServerFilament\Notifications\WebDavAccountPasswordResetNotification;
use N3XT0R\LaravelWebdavServerFilament\Facades\WebDavPasswordRule;

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
            ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.actions.reset_password'))
            ->icon('heroicon-o-key')
            ->schema([
                TextInput::make('password')
                    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.new_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->rules([WebDavPasswordRule::validationRule()])
                    ->default(fn (): string => Str::password(WebDavPasswordRule::generatedLength())),

                TextInput::make('password_confirmation')
                    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.new_password_confirmation'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password'),
            ])
            ->action(function (Model $record, array $data): void {
                $password = (string)$data['password'];

                $result = app(AccountManagementService::class)->update(
                    $record,
                    new AccountUpdateDto(password: $password),
                );

                if ($result) {
                    $this->notifyLinkedUser($record, $password);

                    Notification::make()
                        ->success()
                        ->title(__(
                            'webdav-server-filament::webdav-server-filament.resources.accounts.notifications.password_reset'
                        ))
                        ->send();
                }
            });
    }

    private function notifyLinkedUser(Model $record, string $password): void
    {
        if (! (bool) config('laravel-webdav-server-filament.notifications.enabled', true)) {
            return;
        }

        $notifiable = $record->getAttribute('user') ?? $record->user;

        if (!is_object($notifiable) || !method_exists($notifiable, 'notify')) {
            return;
        }

        $notifiable->notify(new WebDavAccountPasswordResetNotification(
            username: (string)$record->getAttribute('username'),
            password: $password,
        ));
    }
}
