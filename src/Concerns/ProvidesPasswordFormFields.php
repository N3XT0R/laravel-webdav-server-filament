<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use N3XT0R\LaravelWebdavServerFilament\Facades\WebDavPasswordRule;

trait ProvidesPasswordFormFields
{
    protected static function buildPasswordField(): TextInput
    {
        return TextInput::make('password')
            ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.password'))
            ->password()
            ->revealable()
            ->required(fn (string $operation): bool => $operation === 'create')
            ->rules([WebDavPasswordRule::validationRule()])
            ->suffixAction(
                Action::make('generatePassword')
                    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.actions.generate_password'))
                    ->icon(Heroicon::OutlinedKey)
                    ->action(function (Set $set): void {
                        $password = Str::password(WebDavPasswordRule::generatedLength());

                        $set('password', $password);
                        $set('password_confirmation', $password);
                    }),
            );
    }

    protected static function buildPasswordConfirmationField(): TextInput
    {
        return TextInput::make('password_confirmation')
            ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.password_confirmation'))
            ->password()
            ->revealable()
            ->required(fn (string $operation): bool => $operation === 'create')
            ->same('password');
    }
}
