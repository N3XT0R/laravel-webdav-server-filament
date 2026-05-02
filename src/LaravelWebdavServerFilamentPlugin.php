<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Panel;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

class LaravelWebdavServerFilamentPlugin implements Plugin
{
    private bool $adminAccountResourceEnabled = true;

    private ?Closure $userAccountResourceCallback = null;

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
        if ($this->adminAccountResourceEnabled) {
            $panel->resources([WebDavAccountResource::class]);
        }

        if ($this->userAccountResourceCallback !== null) {
            $panel->resources([UserWebDavAccountResource::class]);
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
     * Disable automatic registration of the admin-facing WebDAV account resource.
     *
     * @return static Current plugin instance for fluent configuration.
     */
    public function withoutAdminAccountResource(): static
    {
        $this->adminAccountResourceEnabled = false;

        return $this;
    }

    /**
     * Enable the user-facing WebDAV account resource for all authenticated users.
     *
     * @return static Current plugin instance for fluent configuration.
     */
    public function withUserAccountResource(): static
    {
        $this->userAccountResourceCallback = static fn (mixed $user): bool => true;

        return $this;
    }

    /**
     * Enable the user-facing WebDAV account resource conditionally based on the given callback.
     *
     * The callback receives the currently authenticated user and must return a boolean
     * indicating whether that user may access the resource.
     *
     * @param  callable(mixed): bool  $fn  Callback receiving the authenticated user.
     * @return static Current plugin instance for fluent configuration.
     */
    public function userAccountResourceEnabledUsing(callable $fn): static
    {
        $this->userAccountResourceCallback = $fn instanceof Closure ? $fn : Closure::fromCallable($fn);

        return $this;
    }

    /**
     * Return the configured user account resource authorization callback, when present.
     *
     * Returns null when neither withUserAccountResource() nor userAccountResourceEnabledUsing()
     * has been called, meaning the user-facing resource is disabled.
     *
     * @return Closure|null Callback used to determine user access to the resource.
     */
    public function getUserAccountResourceCallback(): ?Closure
    {
        return $this->userAccountResourceCallback;
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
