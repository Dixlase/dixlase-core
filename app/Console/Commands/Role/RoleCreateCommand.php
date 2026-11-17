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

namespace App\Console\Commands\Role;

use App\Enums\ActorType;
use App\Enums\MemberRole;
use App\Facades\Audit;
use App\Models\CustomRole;
use Illuminate\Console\Command;

/**
 * Create a custom role.
 *
 * Usage:
 *   php artisan dls:role:create writer --name="Writer" --base=editor
 *   php artisan dls:role:create bot --name="Bot" --base=contributor --actor-type=ai --max-members=1
 */
class RoleCreateCommand extends Command
{
    protected $signature = 'dls:role:create
                            {slug : Unique slug of the role}
                            {--name= : English name (defaults to the slug)}
                            {--name-ja= : Japanese name}
                            {--base=editor : Base preset: admin, editor, contributor or guest}
                            {--actor-type=human : human, ai or service}
                            {--max-members= : Maximum number of members (unlimited when omitted)}
                            {--inactive : Create the role inactive}';

    protected $description = 'Create a custom role that starts from a preset role';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');

        if (! preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/', $slug)) {
            $this->error(__('admin/command/role.invalid_slug', ['slug' => $slug]));

            return self::FAILURE;
        }

        if (CustomRole::withTrashed()->where('slug', $slug)->exists()) {
            $this->error(__('admin/command/role.slug_taken', ['slug' => $slug]));

            return self::FAILURE;
        }

        $base = self::parseBaseRole((string) $this->option('base'));
        if ($base === null) {
            $this->error(__('admin/command/role.invalid_base', ['base' => $this->option('base')]));

            return self::FAILURE;
        }

        $actorType = ActorType::tryFrom((string) $this->option('actor-type'));
        if ($actorType === null) {
            $this->error(__('admin/command/role.invalid_actor_type', ['type' => $this->option('actor-type')]));

            return self::FAILURE;
        }

        $maxMembers = $this->option('max-members');
        if ($maxMembers !== null && (! ctype_digit((string) $maxMembers) || (int) $maxMembers < 1)) {
            $this->error(__('admin/command/role.invalid_max_members', ['value' => $maxMembers]));

            return self::FAILURE;
        }

        $name = ['en' => (string) ($this->option('name') ?: $slug)];
        if ($this->option('name-ja')) {
            $name['ja'] = (string) $this->option('name-ja');
        }

        $role = CustomRole::create([
            'slug' => $slug,
            'name' => $name,
            'base_role' => $base,
            'actor_type' => $actorType,
            'max_members' => $maxMembers === null ? null : (int) $maxMembers,
            'is_active' => ! $this->option('inactive'),
        ]);

        Audit::logSecurity('custom_role_created', [
            'severity' => 'warning',
            'outcome' => 'success',
            'context' => [
                'custom_role_id' => $role->id,
                'slug' => $role->slug,
                'base_role' => $base->name,
                'actor_type' => $actorType->value,
                'triggered_by' => 'cli',
            ],
        ]);

        $this->info(__('admin/command/role.created', ['slug' => $slug, 'base' => $base->label()]));

        return self::SUCCESS;
    }

    /**
     * Read a base role given as a case name (`editor`) or its value (`8`).
     * Super Admin is never a valid base.
     */
    public static function parseBaseRole(string $value): ?MemberRole
    {
        $role = ctype_digit($value)
            ? MemberRole::tryFrom((int) $value)
            : (defined(MemberRole::class.'::'.strtoupper($value)) ? constant(MemberRole::class.'::'.strtoupper($value)) : null);

        if (! $role instanceof MemberRole || $role === MemberRole::SUPER_ADMIN) {
            return null;
        }

        return $role;
    }
}
