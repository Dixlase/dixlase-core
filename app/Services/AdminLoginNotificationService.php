<?php

namespace App\Services;

use App\Models\SecuritySetting;
use App\Notifications\AdminLoginNotification;
use App\Traits\LoginNotificationTrait;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 管理画面ログイン通知サービス
 *
 * LoginNotificationTraitを使用してメンバーのログイン通知を処理
 */
class AdminLoginNotificationService
{
    use LoginNotificationTrait;

    /**
     * グローバル設定のキー名を取得
     */
    protected function getGlobalSettingKey(): string
    {
        return 'login_notification_mode';
    }

    /**
     * 設定値を取得する関数を取得（セキュリティ設定から）
     */
    protected function getSettingGetter(): callable
    {
        return fn () => SecuritySetting::getValue($this->getGlobalSettingKey(), '0');
    }

    /**
     * 通知クラス名を取得
     */
    protected function getNotificationClass(): string
    {
        return AdminLoginNotification::class;
    }

    /**
     * ログコンテキスト名を取得
     */
    protected function getLogContext(): string
    {
        return 'Admin login notification';
    }
}
