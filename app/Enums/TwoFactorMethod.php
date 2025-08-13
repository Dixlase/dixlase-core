<?php

namespace App\Enums;

enum TwoFactorMethod: string
{
    case EMAIL = 'email';
    case DEVICE = 'device';
    case BIOMETRIC = 'biometric';
    
    public function label(): string
    {
        return match($this) {
            self::EMAIL => __('admin.settings.members.two_factor_method.options.email'),
            self::DEVICE => __('admin.settings.members.two_factor_method.options.device'),
            self::BIOMETRIC => __('admin.settings.members.two_factor_method.options.biometric'),
        };
    }
    
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}