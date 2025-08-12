<?php

namespace App\Enums;

enum LoginNotificationMode: int
{
    case UseProfileSetting = 0; // ← 全体設定専用
    case Disabled = 1;
    case Always = 2;
    case OnlyNewDevice = 3;

    public function label(): string
    {
        return match ($this) {
            self::UseProfileSetting => __('admin.settings.members.login_notification_mode.options.0'),
            self::Disabled => __('admin.settings.members.login_notification_mode.options.1'),
            self::Always => __('admin.settings.members.login_notification_mode.options.2'),
            self::OnlyNewDevice => __('admin.settings.members.login_notification_mode.options.3'),
        };
    }

    public static function forProfile(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::UseProfileSetting);
    }
}
