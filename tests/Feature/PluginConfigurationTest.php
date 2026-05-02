<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Tests\Feature;

use Filament\Forms\Components\Select;
use Filament\Panel;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;
use N3XT0R\LaravelWebdavServerFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class PluginConfigurationTest extends TestCase
{
    #[Test]
    public function it_registers_webdav_account_resource_in_the_panel_by_default(): void
    {
        $panel = Panel::make()->id('test');

        LaravelWebdavServerFilamentPlugin::make()->register($panel);

        self::assertContains(WebDavAccountResource::class, $panel->getResources());
    }

    #[Test]
    public function it_skips_registering_webdav_account_resource_when_disabled(): void
    {
        $panel = Panel::make()->id('test');

        LaravelWebdavServerFilamentPlugin::make()
            ->withoutAccountResource()
            ->register($panel);

        self::assertNotContains(WebDavAccountResource::class, $panel->getResources());
    }

    #[Test]
    public function it_stores_and_retrieves_the_user_select_callback(): void
    {
        $callback = fn (Select $select): Select => $select->label('Custom User');

        $plugin = LaravelWebdavServerFilamentPlugin::make()
            ->userSelectUsing($callback);

        self::assertSame($callback, $plugin->getUserSelectCallback());
    }

    #[Test]
    public function it_returns_null_for_user_select_callback_when_not_configured(): void
    {
        self::assertNull(LaravelWebdavServerFilamentPlugin::make()->getUserSelectCallback());
    }

    #[Test]
    public function it_enables_notifications_by_default(): void
    {
        $config = require __DIR__ . '/../../config/laravel-webdav-server-filament.php';

        self::assertTrue($config['notifications']['enabled']);
    }
}
