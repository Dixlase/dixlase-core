<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
