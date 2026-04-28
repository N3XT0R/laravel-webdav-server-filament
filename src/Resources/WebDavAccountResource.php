<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Actions\ResetPasswordAction;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource\Pages;
use Throwable;

final class WebDavAccountResource extends Resource
{
    protected static ?string $model = WebDavAccountModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    /**
     * Configure the form fields used by create, edit, and view pages.
     *
     * @param  Schema  $schema  Filament schema instance being configured.
     * @return Schema Configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('username')
                ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.username'))
                ->required()
                ->maxLength(255)
                ->unique(table: 'webdav_accounts', column: 'username', ignoreRecord: true),

            TextInput::make('display_name')
                ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.display_name'))
                ->maxLength(255)
                ->placeholder(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.placeholders.display_name')),

            TextInput::make('password')
                ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.password'))
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->suffixAction(
                    Action::make('generatePassword')
                        ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.actions.generate_password'))
                        ->icon(Heroicon::OutlinedKey)
                        ->action(function (Set $set): void {
                            $password = Str::password(16);

                            $set('password', $password);
                            $set('password_confirmation', $password);
                        }),
                ),

            TextInput::make('password_confirmation')
                ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.password_confirmation'))
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->same('password'),

            static::buildUserSelect(),

            Toggle::make('enabled')
                ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.enabled'))
                ->default(true),

            KeyValue::make('meta')
                ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.meta'))
                ->keyLabel(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.meta_key'))
                ->valueLabel(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.meta_value'))
                ->reorderable(),
        ]);
    }

    /**
     * Configure the account list table including columns, filters, and actions.
     *
     * @param  Table  $table  Filament table instance being configured.
     * @return Table Configured table definition.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.username'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.display_name'))
                    ->searchable()
                    ->placeholder(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.empty.display_name')),

                TextColumn::make('user.name')
                    ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.user'))
                    ->description(fn (Model $record): string => $record->user?->email ?? '')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('enabled')
                    ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.enabled'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('enabled')
                    ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.filters.status'))
                    ->trueLabel(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.filters.active'))
                    ->falseLabel(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.filters.inactive')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ResetPasswordAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable')
                        ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.actions.enable_selected'))
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->action(fn (Collection $records) => $records->each->update(['enabled' => true])),

                    BulkAction::make('disable')
                        ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.actions.disable_selected'))
                        ->icon(Heroicon::OutlinedXCircle)
                        ->action(fn (Collection $records) => $records->each->update(['enabled' => false])),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Return translated singular account model label for Filament.
     *
     * @return string Singular model label.
     */
    public static function getModelLabel(): string
    {
        return __('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.labels.singular');
    }

    /**
     * Return translated plural account model label for Filament.
     *
     * @return string Plural model label.
     */
    public static function getPluralModelLabel(): string
    {
        return __('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.labels.plural');
    }

    /**
     * Return translated navigation label for the account resource.
     *
     * @return string Navigation label.
     */
    public static function getNavigationLabel(): string
    {
        return __('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.navigation.label');
    }

    /**
     * Return Filament routes for account management pages.
     *
     * @return array<string, mixed> Route registrations keyed by page name.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebDavAccounts::route('/'),
            'create' => Pages\CreateWebDavAccount::route('/create'),
            'view' => Pages\ViewWebDavAccount::route('/{record}'),
            'edit' => Pages\EditWebDavAccount::route('/{record}/edit'),
        ];
    }

    private static function buildUserSelect(): Select
    {
        $select = Select::make('user_id')
            ->label(__('laravel-webdav-server-filament::laravel-webdav-server-filament.resources.accounts.fields.user'))
            ->required();

        try {
            $callback = LaravelWebdavServerFilamentPlugin::get()->getUserSelectCallback();
        } catch (Throwable) {
            $callback = null;
        }

        if ($callback !== null) {
            return $callback($select);
        }

        $userModel = config('webdav-server.auth.user_model');

        return $select
            ->searchable()
            ->getSearchResultsUsing(fn (string $search): array => $userModel::query()
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->limit(50)
                ->pluck('name', 'id')
                ->toArray())
            ->getOptionLabelUsing(fn (mixed $value): string => $userModel::find($value)?->name ?? (string) $value);
    }
}
