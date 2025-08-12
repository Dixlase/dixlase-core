<?php

namespace App\Enums;

enum AppearanceMode: int
{
    case Auto = 0;
    case Light = 1;
    case Dark = 2;

    public function label(): string
    {
        $key = match ($this) {
            self::Auto => 'auto',
            self::Light => 'light',
            self::Dark => 'dark',
        };

        return __("member.appearance.{$key}");
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::Auto => 'auto',
            self::Light => 'light',
            self::Dark => 'dark',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($mode) => [
            $mode->value => $mode->label()
        ])->toArray();
    }

    public static function translationOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn($mode) => [
            $mode->value => "member.appearance.{$mode->translationKey()}"
        ])->toArray();
    }
}
