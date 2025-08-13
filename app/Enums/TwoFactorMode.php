<?php

namespace App\Enums;

enum TwoFactorMode: int
{
    case UseProfileSetting = 0; // ← 全体設定専用
    case Disabled = 1;
    case Always = 2;
    case OnlyNewDevice = 3;

    public function label(): string
    {
        return match ($this) {
            self::UseProfileSetting => __('admin.settings.members.two_factor_mode.options.0'),
            self::Disabled => __('admin.settings.members.two_factor_mode.options.1'),
            self::Always => __('admin.settings.members.two_factor_mode.options.2'),
            self::OnlyNewDevice => __('admin.settings.members.two_factor_mode.options.3'),
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

    public static function forProfile(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::UseProfileSetting);
    }
}
