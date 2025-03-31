<?php

namespace App\Enums;

enum LoginNotificationModeMember: int
{
    case Disabled = 1;
    case Always = 2;
    case OnlyNewDevice = 3;

    public function label(): string
    {
        return match ($this) {
            self::Disabled => '無効',
            self::Always => '常に有効',
            self::OnlyNewDevice => '異なる端末/IP時のみ有効',
        };
    }
}
