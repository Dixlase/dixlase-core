<?php

namespace App\Enums;

enum TwoFactorMode: int
{
    case Disabled = 0;
    case OnlyNewDevice = 1;
    case Always = 2;
    case UseProfileSetting = 3; // ← 全体設定専用

    public function label(): string
    {
        return match ($this) {
            self::Disabled => __('common.two_factor_mode.options.0'),
            self::OnlyNewDevice => __('common.two_factor_mode.options.1'),
            self::Always => __('common.two_factor_mode.options.2'),
            self::UseProfileSetting => __('common.two_factor_mode.options.3'),
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
        return match ($this) {
            self::Disabled => 'common.two_factor_mode.options.0',
            self::OnlyNewDevice => 'common.two_factor_mode.options.1',
            self::Always => 'common.two_factor_mode.options.2',
            self::UseProfileSetting => 'common.two_factor_mode.options.3',
        };
    }

    public static function forProfile(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::UseProfileSetting);
    }
}
