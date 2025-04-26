<?php

namespace App\Enums;

enum MemberRole: int
{
    case SUPER_ADMIN = 1;
    case ADMIN = 2;
    case EDITOR = 3;
    case AUTHOR = 4;
    case CONTRIBUTOR = 5;

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => '特権管理者',
            self::ADMIN => '管理者',
            self::EDITOR => '編集者',
            self::AUTHOR => '投稿者',
            self::CONTRIBUTOR => '寄稿者',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($role) => [
            $role->value => $role->label()
        ])->toArray();
    }
}
