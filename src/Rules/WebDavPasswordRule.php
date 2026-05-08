<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Rules;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Validation\Rules\Password;

final class WebDavPasswordRule
{
    public function __construct(private readonly ConfigRepository $config)
    {
    }

    public function validationRule(): Password
    {
        $rule = Password::min($this->minLength());

        if ((bool) $this->config->get('laravel-webdav-server-filament.password.require_mixed_case', true)) {
            $rule = $rule->mixedCase();
        }

        if ((bool) $this->config->get('laravel-webdav-server-filament.password.require_numbers', true)) {
            $rule = $rule->numbers();
        }

        if ((bool) $this->config->get('laravel-webdav-server-filament.password.require_symbols', true)) {
            $rule = $rule->symbols();
        }

        return $rule;
    }

    public function generatedLength(): int
    {
        return $this->minLength();
    }

    private function minLength(): int
    {
        return (int) $this->config->get('laravel-webdav-server-filament.password.min_length', 16);
    }
}
