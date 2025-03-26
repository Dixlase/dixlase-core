<?php

namespace App\Enums;

enum GlobalTwoFactorMode: int
{
    case PerMember = 0;
    case Disabled = 1;
    case Always = 2;
    case Smart = 3;

    public function label(): string
    {
        return match ($this) {
            self::PerMember => 'メンバーのプロフィール設定を反映',
            self::Disabled => '無効',
            self::Always => '常に有効',
            self::Smart => '異なる端末・IPのみ有効',
        };
    }
}
