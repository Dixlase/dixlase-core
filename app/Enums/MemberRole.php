<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
 * メンバーロール定義
 */
enum MemberRole: int
{
    case SUPER_ADMIN = 10;
    case ADMIN = 9;
    case EDITOR = 8;
    case CONTRIBUTOR = 6;
    case GUEST = 1;

    public function label(): string
    {
        $key = match ($this) {
            self::SUPER_ADMIN => 'super_admin',
            self::ADMIN => 'admin',
            self::EDITOR => 'editor',
            self::CONTRIBUTOR => 'contributor',
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
            self::CONTRIBUTOR => 'contributor',
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
