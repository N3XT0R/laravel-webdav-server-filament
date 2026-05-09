<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServerFilament\Events\WebDavAccountDeletedEvent;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource\Pages;
use N3XT0R\LaravelWebdavServerFilament\Facades\WebDavPasswordRule;
use Throwable;

final class UserWebDavAccountResource extends Resource
{
    protected static ?string $model = WebDavAccountModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    /**
     * Determine whether this resource should appear in panel navigation.
     *
     * Returns true only when the user account resource callback is configured on the current panel
     * and the callback approves the currently authenticated user. Unauthenticated requests always
     * return false.
     *
     * @return bool True when the authenticated user may access this resource.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        try {
            $callback = LaravelWebdavServerFilamentPlugin::get()->getUserAccountResourceCallback();
        } catch (Throwable) {
            return false;
        }

        return $callback !== null && $callback($user);
    }

    /**
     * Return a query scoped to the accounts owned by the authenticated user.
     *
     * @return Builder Eloquent query restricted to the current user's accounts.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    /**
     * Configure the form fields for create and edit pages.
     *
     * The user_id field is intentionally absent; it is automatically set to the
     * authenticated user on creation.
     *
     * @param  Schema  $schema  Filament schema instance being configured.
     * @return Schema Configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('username')
                ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.username'))
                ->required()
                ->maxLength(255)
                ->unique(table: 'webdav_accounts', column: 'username', ignoreRecord: true),

            TextInput::make('display_name')
                ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.display_name'))
                ->maxLength(255)
                ->placeholder(__('webdav-server-filament::webdav-server-filament.resources.accounts.placeholders.display_name')),

            TextInput::make('password')
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
                ),

            TextInput::make('password_confirmation')
                ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.password_confirmation'))
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->same('password'),

            Toggle::make('enabled')
                ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.enabled'))
                ->default(true),

            ...((bool) config('laravel-webdav-server-filament.user_resource.show_meta', true) ? [
                KeyValue::make('meta')
                    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.meta'))
                    ->keyLabel(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.meta_key'))
                    ->valueLabel(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.meta_value'))
                    ->reorderable(),
            ] : []),
        ]);
    }

    /**
     * Configure the account list table for the authenticated user's own accounts.
     *
     * @param  Table  $table  Filament table instance being configured.
     * @return Table Configured table definition.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.username'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.display_name'))
                    ->searchable()
                    ->placeholder(__('webdav-server-filament::webdav-server-filament.resources.accounts.empty.display_name')),

                IconColumn::make('enabled')
                    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.enabled'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('enabled')
                    ->label(__('webdav-server-filament::webdav-server-filament.resources.accounts.filters.status'))
                    ->trueLabel(__('webdav-server-filament::webdav-server-filament.resources.accounts.filters.active'))
                    ->falseLabel(__('webdav-server-filament::webdav-server-filament.resources.accounts.filters.inactive')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                static::deleteAction(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    /**
     * Return translated singular account model label for Filament.
     *
     * @return string Singular model label.
     */
    public static function getModelLabel(): string
    {
        return __('webdav-server-filament::webdav-server-filament.resources.accounts.labels.singular');
    }

    /**
     * Return translated plural account model label for Filament.
     *
     * @return string Plural model label.
     */
    public static function getPluralModelLabel(): string
    {
        return __('webdav-server-filament::webdav-server-filament.resources.accounts.labels.plural');
    }

    /**
     * Return translated navigation label for the account resource.
     *
     * @return string Navigation label.
     */
    public static function getNavigationLabel(): string
    {
        return __('webdav-server-filament::webdav-server-filament.resources.accounts.navigation.label');
    }

    /**
     * Return Filament routes for user account management pages.
     *
     * @return array<string, mixed> Route registrations keyed by page name.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserWebDavAccounts::route('/'),
            'create' => Pages\CreateUserWebDavAccount::route('/create'),
            'view' => Pages\ViewUserWebDavAccount::route('/{record}'),
            'edit' => Pages\EditUserWebDavAccount::route('/{record}/edit'),
        ];
    }

    private static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->after(fn (Model $record): mixed => (new WebDavAccountDeletedEvent($record))->dispatchForListeners());
    }
}
