<?php

namespace N3XT0R\LaravelWebdavServerFilament;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use N3XT0R\LaravelWebdavServerFilament\Commands\LaravelWebdavServerFilamentCommand;
use N3XT0R\LaravelWebdavServerFilament\Testing\TestsLaravelWebdavServerFilament;

class LaravelWebdavServerFilamentServiceProvider extends PackageServiceProvider
{
    public static string $name = 'laravel-webdav-server-filament';

    public static string $viewNamespace = 'laravel-webdav-server-filament';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('n3xt0r/laravel-webdav-server-filament');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/laravel-webdav-server-filament/{$file->getFilename()}"),
                ], 'laravel-webdav-server-filament-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsLaravelWebdavServerFilament());
    }

    protected function getAssetPackageName(): ?string
    {
        return 'n3xt0r/laravel-webdav-server-filament';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('laravel-webdav-server-filament', __DIR__ . '/../resources/dist/components/laravel-webdav-server-filament.js'),
            // Css::make('laravel-webdav-server-filament-styles', __DIR__ . '/../resources/dist/laravel-webdav-server-filament.css'),
            // Js::make('laravel-webdav-server-filament-scripts', __DIR__ . '/../resources/dist/laravel-webdav-server-filament.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            LaravelWebdavServerFilamentCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_laravel-webdav-server-filament_table',
        ];
    }
}
