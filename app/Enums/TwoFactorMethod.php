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
            self::EMAIL => 'メール認証',
            self::DEVICE => 'デバイス認証',
            self::BIOMETRIC => '生体認証',
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