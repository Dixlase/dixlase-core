<?php


namespace App\Enums;

enum MembersTwoFactorMode: int
{
    case Disabled = 0;
    case Always = 1;
    case Smart = 2;

    public function label(): string
    {
        return match ($this) {
            self::Disabled => '2段階認証なし',
            self::Always   => '常に有効',
            self::Smart    => '異なる端末/IP時のみ有効',
        };
    }
}
