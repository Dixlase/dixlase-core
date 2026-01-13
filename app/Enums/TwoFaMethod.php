<?php

namespace App\Enums;

enum TwoFaMethod: int
{
    case EMAIL = 0;
    case PASSKEY = 1;
    
    public function label(): string
    {
        return match($this) {
            self::EMAIL => __('two_fa.method.email'),
            self::PASSKEY => __('two_fa.method.passkey'),
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
        return match($this) {
            self::EMAIL => 'two_fa.method.email',
            self::PASSKEY => 'two_fa.method.passkey',
        };
    }

    /**
     * セキュリティレベルを取得（5段階評価）
     */
    public function securityLevel(): int
    {
        return match($this) {
            self::PASSKEY => 5,  // 最も安全
            self::EMAIL => 3,    // 中程度のセキュリティ
        };
    }

    /**
     * セキュリティレベルのラベル
     */
    public function securityLevelLabel(): string
    {
        return match($this) {
            self::PASSKEY => __('two_fa.security.level.very_high'),
            self::EMAIL => __('two_fa.security.level.medium'),
        };
    }

    /**
     * セキュリティの説明
     */
    public function securityDescription(): string
    {
        return match($this) {
            self::PASSKEY => __('two_fa.security.description.passkey'),
            self::EMAIL => __('two_fa.security.description.email'),
        };
    }

    /**
     * 推奨される認証方法かどうか
     */
    public function isRecommended(): bool
    {
        return match($this) {
            self::PASSKEY => true,
            self::EMAIL => false,
        };
    }

    public static function forGlobalSettings(): array
    {
        return self::cases();
    }
}