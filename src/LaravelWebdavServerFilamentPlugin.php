<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Panel;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

class LaravelWebdavServerFilamentPlugin implements Plugin
{
    private bool $accountResourceEnabled = true;

    private ?Closure $userSelectCallback = null;

    /**
     * Return the unique Filament plugin identifier used for panel registration.
     *
     * @return string Plugin identifier.
     */
    public function getId(): string
    {
        return 'laravel-webdav-server-filament';
    }

    /**
     * Register package resources with the configured Filament panel.
     *
     * @param  Panel  $panel  Panel receiving this plugin configuration.
     */
    public function register(Panel $panel): void
    {
        if ($this->accountResourceEnabled) {
            $panel->resources([WebDavAccountResource::class]);
        }
    }

    /**
     * Boot the plugin after Filament has registered it on a panel.
     *
     * @param  Panel  $panel  Panel booting this plugin.
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Disable automatic registration of the WebDAV account resource.
     *
     * @return static Current plugin instance for fluent configuration.
     */
    public function withoutAccountResource(): static
    {
        $this->accountResourceEnabled = false;

        return $this;
    }

    /**
     * Configure the user select field used by the WebDAV account resource.
     *
     * @param  callable(Select): Select  $fn  Callback that receives and returns the user select component.
     * @return static Current plugin instance for fluent configuration.
     */
    public function userSelectUsing(callable $fn): static
    {
        $this->userSelectCallback = $fn instanceof Closure ? $fn : Closure::fromCallable($fn);

        return $this;
    }

    /**
     * Return the configured user select customization callback, when present.
     *
     * @return Closure|null Callback used to customize the resource's user select field.
     */
    public function getUserSelectCallback(): ?Closure
    {
        return $this->userSelectCallback;
    }

    /**
     * Resolve a plugin instance from the Laravel container.
     *
     * @return static Plugin instance.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Resolve the plugin instance currently registered with Filament.
     *
     * @return static Registered Filament plugin instance.
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
