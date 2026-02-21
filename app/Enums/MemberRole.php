<?php

namespace App\Enums;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * メンバーロール定義
 */
enum MemberRole: int
{
    case SUPER_ADMIN = 10;
    case ADMIN = 9;
    case EDITOR = 8;
    case AUTHOR = 7;
    case CONTRIBUTOR = 6;
    case RECEPTIONIST = 5;
    case GUEST = 1;

    public function label(): string
    {
        $key = match ($this) {
            self::SUPER_ADMIN => 'super_admin',
            self::ADMIN => 'admin',
            self::EDITOR => 'editor',
            self::AUTHOR => 'author',
            self::CONTRIBUTOR => 'contributor',
            self::RECEPTIONIST => 'receptionist',
            self::GUEST => 'guest',
        };

        return __("member.roles.{$key}");
    }

    public function priority(): int
    {
        return $this->value; // 今回は value と priority を同じにする
    }

    public function canAccess(MemberRole $requiredRole): bool
    {
        return $this->priority() >= $requiredRole->priority();
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'super_admin',
            self::ADMIN => 'admin',
            self::EDITOR => 'editor',
            self::AUTHOR => 'author',
            self::CONTRIBUTOR => 'contributor',
            self::RECEPTIONIST => 'receptionist',
            self::GUEST => 'guest',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($role) => [
            $role->value => $role->label(),
        ])->toArray();
    }

    public static function translationOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($role) => [
            $role->value => $role->label(),
        ])->toArray();
    }
}
