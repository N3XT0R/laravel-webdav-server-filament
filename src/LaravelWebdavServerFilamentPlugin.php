<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;

class LaravelWebdavServerFilamentPlugin implements Plugin
{
    private bool $accountResourceEnabled = true;

    private ?Closure $userSelectCallback = null;

    public function getId(): string
    {
        return 'laravel-webdav-server-filament';
    }

    public function register(Panel $panel): void
    {
        if ($this->accountResourceEnabled) {
            $panel->resources([WebDavAccountResource::class]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function withoutAccountResource(): static
    {
        $this->accountResourceEnabled = false;

        return $this;
    }

    public function userSelectUsing(callable $fn): static
    {
        $this->userSelectCallback = $fn instanceof Closure ? $fn : Closure::fromCallable($fn);

        return $this;
    }

    public function getUserSelectCallback(): ?Closure
    {
        return $this->userSelectCallback;
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
