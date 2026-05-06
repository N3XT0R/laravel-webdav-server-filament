<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Rules;

use Illuminate\Validation\Rules\Password;

final class WebDavPasswordRule
{
    public static function validationRule(): Password
    {
        $rule = Password::min(self::minLength());

        if ((bool) config('laravel-webdav-server-filament.password.require_mixed_case', true)) {
            $rule = $rule->mixedCase();
        }

        if ((bool) config('laravel-webdav-server-filament.password.require_numbers', true)) {
            $rule = $rule->numbers();
        }

        if ((bool) config('laravel-webdav-server-filament.password.require_symbols', true)) {
            $rule = $rule->symbols();
        }

        return $rule;
    }

    public static function generatedLength(): int
    {
        return self::minLength();
    }

    private static function minLength(): int
    {
        return (int) config('laravel-webdav-server-filament.password.min_length', 16);
    }
}
