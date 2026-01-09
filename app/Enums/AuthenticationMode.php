<?php

namespace App\Enums;

/**
 * 認証モード（二段階認証・通知設定共通）
 * 
 * メンバーとユーザーの両方で使用可能
 * 二段階認証と通知設定の両方で使用可能
 */
enum AuthenticationMode: int
{
    case Disabled = 0;              // 無効
    case DifferentDevice = 1;       // 異なるデバイス・IPでのログイン時のみ
    case Always = 2;                // 常に有効
    case UseProfileSetting = 3;     // プロフィール設定に従う（全体設定専用）

    /**
     * ラベルを取得（二段階認証用）
     */
    public function twoFactorLabel(): string
    {
        return match ($this) {
            self::Disabled => __('components.two_fa.authentication_mode.disabled'),
            self::DifferentDevice => __('components.two_fa.authentication_mode.different_device'),
            self::Always => __('components.two_fa.authentication_mode.always'),
            self::UseProfileSetting => __('components.two_fa.authentication_mode.use_profile_setting'),
        };
    }

    /**
     * ラベルを取得（通知設定用）
     */
    public function notificationLabel(): string
    {
        return match ($this) {
            self::Disabled => __('auth.authentication_mode.notification.disabled'),
            self::DifferentDevice => __('auth.authentication_mode.notification.different_device'),
            self::Always => __('auth.authentication_mode.notification.always'),
            self::UseProfileSetting => __('auth.authentication_mode.notification.use_profile_setting'),
        };
    }

    /**
     * 翻訳キーを取得（二段階認証用）
     */
    public function twoFactorTranslationKey(): string
    {
        return match ($this) {
            self::Disabled => 'components.two_fa.authentication_mode.disabled',
            self::DifferentDevice => 'components.two_fa.authentication_mode.different_device',
            self::Always => 'components.two_fa.authentication_mode.always',
            self::UseProfileSetting => 'components.two_fa.authentication_mode.use_profile_setting',
        };
    }

    /**
     * 翻訳キーを取得（通知設定用）
     */
    public function notificationTranslationKey(): string
    {
        return match ($this) {
            self::Disabled => 'auth.authentication_mode.notification.disabled',
            self::DifferentDevice => 'auth.authentication_mode.notification.different_device',
            self::Always => 'auth.authentication_mode.notification.always',
            self::UseProfileSetting => 'auth.authentication_mode.notification.use_profile_setting',
        };
    }

    /**
     * 二段階認証用のオプション配列を取得
     */
    public static function twoFactorOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->twoFactorLabel();
        }
        return $options;
    }

    /**
     * 二段階認証用の翻訳キー配列を取得
     */
    public static function twoFactorTranslationOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->twoFactorTranslationKey();
        }
        return $options;
    }

    /**
     * 通知設定用のオプション配列を取得
     */
    public static function notificationOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->notificationLabel();
        }
        return $options;
    }

    /**
     * 通知設定用の翻訳キー配列を取得
     */
    public static function notificationTranslationOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->notificationTranslationKey();
        }
        return $options;
    }

    /**
     * プロフィール設定用（UseProfileSettingを除く）
     */
    public static function forProfile(): array
    {
        return array_filter(self::cases(), fn(self $case) => $case !== self::UseProfileSetting);
    }

    /**
     * プロフィール設定用の二段階認証オプション
     */
    public static function twoFactorProfileOptions(): array
    {
        $options = [];
        foreach (self::forProfile() as $case) {
            $options[$case->value] = $case->twoFactorLabel();
        }
        return $options;
    }

    /**
     * プロフィール設定用の通知オプション
     */
    public static function notificationProfileOptions(): array
    {
        $options = [];
        foreach (self::forProfile() as $case) {
            $options[$case->value] = $case->notificationLabel();
        }
        return $options;
    }

    /**
     * 後方互換性のため（旧TwoFactorMode）
     */
    public function label(): string
    {
        return $this->twoFactorLabel();
    }

    /**
     * 後方互換性のため（旧TwoFactorMode）
     */
    public function translationKey(): string
    {
        return $this->twoFactorTranslationKey();
    }

    /**
     * 後方互換性のため（旧TwoFactorMode）
     */
    public static function options(): array
    {
        return self::twoFactorOptions();
    }

    /**
     * 後方互換性のため（旧TwoFactorMode）
     */
    public static function translationOptions(): array
    {
        return self::twoFactorTranslationOptions();
    }
}
