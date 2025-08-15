<?php

namespace App\Enums;

enum LoginNotificationMode: int
{
    case Disabled = 0;
    case UseProfileSetting = 1; // ← 全体設定専用
    case OnlyNewDevice = 2;
    case Always = 3;

    public function label(): string
    {
        return match ($this) {
            self::Disabled => __('admin.settings.members.login_notification_mode.options.0'),
            self::UseProfileSetting => __('admin.settings.members.login_notification_mode.options.1'),
            self::OnlyNewDevice => __('admin.settings.members.login_notification_mode.options.2'),
            self::Always => __('admin.settings.members.login_notification_mode.options.3'),
        };
    }

    public static function forProfile(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::UseProfileSetting);
    }
}
