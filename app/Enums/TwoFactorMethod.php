<?php

namespace App\Enums;

enum TwoFactorMethod: int
{
    case EMAIL = 0;
    case DEVICE = 1;
    case BIOMETRIC = 2;
    case USE_PROFILE_SETTING = 3;
    
    public function label(): string
    {
        return match($this) {
            self::EMAIL => __('common.two_factor_method.numbered_options.0'),
            self::DEVICE => __('common.two_factor_method.numbered_options.1'),
            self::BIOMETRIC => __('common.two_factor_method.numbered_options.2'),
            self::USE_PROFILE_SETTING => __('common.two_factor_method.numbered_options.3'),
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
            self::DEVICE => 'common.two_factor_method.numbered_options.1',
            self::BIOMETRIC => 'common.two_factor_method.numbered_options.2',
            self::USE_PROFILE_SETTING => 'common.two_factor_method.numbered_options.3',
        };
    }

    public static function forGlobalSettings(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::USE_PROFILE_SETTING);
    }
}