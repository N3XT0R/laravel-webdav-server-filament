<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Validation\Rules\Password;

/**
 * @method static Password validationRule()
 * @method static int generatedLength()
 *
 * @see \N3XT0R\LaravelWebdavServerFilament\Rules\WebDavPasswordRule
 */
class WebDavPasswordRule extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \N3XT0R\LaravelWebdavServerFilament\Rules\WebDavPasswordRule::class;
    }
}
