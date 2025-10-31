<?php

namespace App\Enums;

enum TwoFactorMethod: int
{
    case EMAIL = 0;
    case PASSKEY = 1;
    
    public function label(): string
    {
        return match($this) {
            self::EMAIL => __('common.two_factor_method.numbered_options.0'),
            self::PASSKEY => __('common.two_factor_method.numbered_options.1'),
        };
    }
    
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public static function translationOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->translationKey();
        }
        return $options;
    }

    public function translationKey(): string
    {
        return match($this) {
            self::EMAIL => 'common.two_factor_method.numbered_options.0',
            self::PASSKEY => 'common.two_factor_method.numbered_options.1',
        };
    }

    public static function forGlobalSettings(): array
    {
        return self::cases();
    }
}