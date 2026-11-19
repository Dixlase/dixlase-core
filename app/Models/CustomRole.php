<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActorType;
use App\Enums\MemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * A role between the MemberRole presets.
 *
 * A custom role starts from a preset (`base_role`) and narrows or widens it
 * with per-site overrides (CustomRolePermissionOverride). The role itself is
 * network-wide; the overrides belong to a site.
 *
 * `members.role` stays the member's effective preset: assigning a custom
 * role writes its base_role there, and changing a role's base_role rewrites
 * it on every member holding the role. Every check that reads `members.role`
 * directly (safe mode, lockdown, the member hierarchy guard) therefore stays
 * correct without knowing custom roles exist, and only the overrides are
 * layered on top -- see CustomRolePermissionResolver.
 *
 * A custom role never has a Super Admin base, and a Super Admin is never
 * given one: Super Admin bypasses every check, so overrides could not apply,
 * and assigning one would silently demote the member.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string> $name
 * @property array<string, string>|null $description
 * @property MemberRole $base_role
 * @property ActorType $actor_type
 * @property int $sort_order
 * @property string|null $color
 * @property int|null $max_members
 * @property bool $is_active
 * @property int|null $created_by
 */
class CustomRole extends Model
{
    use SoftDeletes;

    protected $table = 'custom_roles';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'base_role',
        'actor_type',
        'sort_order',
        'color',
        'max_members',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'base_role' => MemberRole::class,
            'actor_type' => ActorType::class,
            'sort_order' => 'integer',
            'max_members' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CustomRole $role): void {
            if ($role->base_role === MemberRole::SUPER_ADMIN) {
                throw new InvalidArgumentException('A custom role cannot have a Super Admin base role.');
            }
        });

        static::updated(function (CustomRole $role): void {
            if ($role->wasChanged('base_role')) {
                // Keep members.role equal to the base role (see class doc).
                Member::where('custom_role_id', $role->id)
                    ->update(['role' => $role->base_role->value]);
            }
        });
    }

    /**
     * Overrides of this role on the current site.
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(CustomRolePermissionOverride::class, 'custom_role_id');
    }

    /**
     * Members holding this role.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'custom_role_id');
    }

    /**
     * Name in the current locale, falling back to English, then the slug.
     */
    public function displayName(): string
    {
        $name = $this->name ?? [];

        return $name[app()->getLocale()] ?? $name['en'] ?? $this->slug;
    }

    /**
     * Give this role to a member, setting their preset to the base role.
     *
     * @throws InvalidArgumentException when the member is a Super Admin, the
     *                                  role is deleted, or it is full
     */
    public function assignTo(Member $member): void
    {
        if ($this->trashed()) {
            throw new InvalidArgumentException('A deleted custom role cannot be assigned.');
        }

        if ($member->getMemberRole() === MemberRole::SUPER_ADMIN) {
            throw new InvalidArgumentException('A Super Admin cannot be given a custom role.');
        }

        if ($this->max_members !== null
            && $member->custom_role_id !== $this->id
            && $this->members()->count() >= $this->max_members) {
            throw new InvalidArgumentException('The custom role has reached its member limit.');
        }

        // custom_role_id is deliberately not mass assignable on Member.
        $member->forceFill([
            'custom_role_id' => $this->id,
            'role' => $this->base_role,
        ])->save();

        $member->unsetRelation('customRole');
    }
}
