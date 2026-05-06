<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Tests\Unit\Rules;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use N3XT0R\LaravelWebdavServerFilament\Rules\WebDavPasswordRule;
use N3XT0R\LaravelWebdavServerFilament\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class WebDavPasswordRuleTest extends TestCase
{
    #[Test]
    public function generated_length_returns_configured_value(): void
    {
        Config::set('laravel-webdav-server-filament.password.min_length', 20);

        self::assertSame(20, WebDavPasswordRule::generatedLength());
    }

    #[Test]
    public function generated_length_returns_default_16_when_not_set(): void
    {
        Config::set('laravel-webdav-server-filament.password', []);

        self::assertSame(16, WebDavPasswordRule::generatedLength());
    }

    #[Test]
    public function validation_rule_rejects_password_shorter_than_min_length(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'Short1!'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertTrue($validator->fails());
    }

    #[Test]
    public function validation_rule_accepts_password_meeting_all_requirements(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => true,
            'require_numbers'    => true,
            'require_symbols'    => true,
        ]);

        $validator = Validator::make(
            ['password' => 'ValidP@ssword123'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertFalse($validator->fails());
    }

    #[Test]
    public function validation_rule_rejects_password_without_mixed_case_when_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => true,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'alllowercasepassword'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertTrue($validator->fails());
    }

    #[Test]
    public function validation_rule_accepts_lowercase_only_when_mixed_case_not_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'alllowercasepassword'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertFalse($validator->fails());
    }

    #[Test]
    public function validation_rule_rejects_password_without_numbers_when_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => true,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'NoNumbersHereAtAll'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertTrue($validator->fails());
    }

    #[Test]
    public function validation_rule_accepts_no_numbers_when_not_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'NoNumbersHereAtAll'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertFalse($validator->fails());
    }

    #[Test]
    public function validation_rule_rejects_password_without_symbols_when_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => true,
        ]);

        $validator = Validator::make(
            ['password' => 'NoSymbolsHere1234'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertTrue($validator->fails());
    }

    #[Test]
    public function validation_rule_accepts_no_symbols_when_not_required(): void
    {
        Config::set('laravel-webdav-server-filament.password', [
            'min_length'         => 16,
            'require_mixed_case' => false,
            'require_numbers'    => false,
            'require_symbols'    => false,
        ]);

        $validator = Validator::make(
            ['password' => 'NoSymbolsHere1234'],
            ['password' => WebDavPasswordRule::validationRule()],
        );

        self::assertFalse($validator->fails());
    }
}
