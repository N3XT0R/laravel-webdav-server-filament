<?php

namespace N3XT0R\LaravelWebdavServerFilament\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilament
 */
class LaravelWebdavServerFilament extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilament::class;
    }
}
