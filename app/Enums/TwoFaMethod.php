<?php

namespace App\Enums;

enum TwoFaMethod: int
{
    case EMAIL = 0;
    case PASSKEY = 1;
    
    public function label(): string
    {
        return match($this) {
            self::EMAIL => __('two_fa.method.email'),
            self::PASSKEY => __('two_fa.method.passkey'),
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
            self::EMAIL => 'two_fa.method.email',
            self::PASSKEY => 'two_fa.method.passkey',
        };
    }

    public static function forGlobalSettings(): array
    {
        return self::cases();
    }
}