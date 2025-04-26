<?php

namespace App\Enums;

enum AppearanceMode: int
{
    case Auto = 0;
    case Light = 1;
    case Dark = 2;


    public function label(): string
    {
        return match ($this) {
            self::Auto => '自動（PC設定に従う）',
            self::Light => 'ライト',
            self::Dark => 'ダーク',
        };
    }
}
