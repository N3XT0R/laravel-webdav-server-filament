<?php

declare(strict_types=1);

use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;
use N3XT0R\LaravelWebdavServerFilament\Resources\WebDavAccountResource;
use Filament\Forms\Components\Select;
use Filament\Panel;

it('registers WebDavAccountResource in the panel by default', function (): void {
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('resources')
        ->once()
        ->with([WebDavAccountResource::class])
        ->andReturnSelf();

    $plugin = LaravelWebdavServerFilamentPlugin::make();
    $plugin->register($panel);
});

it('skips registering WebDavAccountResource when withoutAccountResource is called', function (): void {
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('resources')->never();

    $plugin = LaravelWebdavServerFilamentPlugin::make()->withoutAccountResource();
    $plugin->register($panel);
});

it('stores and retrieves the userSelectUsing callback', function (): void {
    $callback = fn (Select $select) => $select->label('Custom User');
    $plugin = LaravelWebdavServerFilamentPlugin::make()->userSelectUsing($callback);

    expect($plugin->getUserSelectCallback())->toBe($callback);
});

it('returns null for userSelectCallback when not configured', function (): void {
    $plugin = LaravelWebdavServerFilamentPlugin::make();
    expect($plugin->getUserSelectCallback())->toBeNull();
});
