<?php

namespace App\Enums;

enum LoginNotificationModeGlobal: int
{
    case UseProfileSetting = 0; // ← 全体設定専用
    case Disabled = 1;
    case Always = 2;
    case OnlyNewDevice = 3;

    public function label(): string
    {
        return match ($this) {
            self::UseProfileSetting => 'メンバーのプロフィール設定を反映',
            self::Disabled => '無効',
            self::Always => '常に有効',
            self::OnlyNewDevice => '異なる端末・IPのみ有効',
        };
    }
}
