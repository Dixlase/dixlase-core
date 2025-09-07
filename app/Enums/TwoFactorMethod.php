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
            self::EMAIL => __('admin.settings.members.settings.two_factor_method.options.0'),
            self::DEVICE => __('admin.settings.members.settings.two_factor_method.options.1'),
            self::BIOMETRIC => __('admin.settings.members.settings.two_factor_method.options.2'),
            self::USE_PROFILE_SETTING => __('admin.settings.members.settings.two_factor_method.options.3'),
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

    public static function forGlobalSettings(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::USE_PROFILE_SETTING);
    }
}