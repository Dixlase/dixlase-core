<?php

namespace App\Enums;

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
        return match ($this) {
            self::SUPER_ADMIN => '特権管理者',
            self::ADMIN => '管理者',
            self::EDITOR => '編集者',
            self::AUTHOR => '投稿者',
            self::CONTRIBUTOR => '寄稿者',
            self::RECEPTIONIST => '受付',
            self::GUEST => 'ゲスト',
        };
    }

    public function priority(): int
    {
        return $this->value; // 今回は value と priority を同じにする
    }

    public function canAccess(MemberRole $requiredRole): bool
    {
        return $this->priority() >= $requiredRole->priority();
    }


    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($role) => [
            $role->value => $role->label()
        ])->toArray();
    }
}
