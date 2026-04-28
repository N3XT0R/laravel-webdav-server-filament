<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Workbench\App\Models\User;

class DatabaseTestCase extends TestCase
{

    use LazilyRefreshDatabase;

    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $config = $app->make(Repository::class);

        $config->set([
            'auth.defaults.provider' => 'users',
            'auth.providers.users.model' => User::class,
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}