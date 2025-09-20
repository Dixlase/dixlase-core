<?php

namespace App\Enums;

enum LoginNotificationMode: int
{
    case Disabled = 0;
    case OnlyNewDevice = 1;
    case Always = 2;
    case UseProfileSetting = 3; // ← 全体設定専用

    public function label(): string
    {
        return match ($this) {
            self::Disabled => __('admin.login_notification_mode.options.0'),
            self::OnlyNewDevice => __('admin.login_notification_mode.options.1'),
            self::Always => __('admin.login_notification_mode.options.2'),
            self::UseProfileSetting => __('admin.login_notification_mode.options.3'),
        };
    }

    public static function forProfile(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::UseProfileSetting);
    }
}
