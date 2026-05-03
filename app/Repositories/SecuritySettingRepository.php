<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Models\GlobalSetting;
use App\Models\SecuritySetting;
use App\Services\Site\Exceptions\UnknownSettingException;
use App\Services\Site\SettingDefinitionRegistry;
use App\Services\Site\SettingResolver;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * Security-policy settings repository. Delegates to SettingResolver so
 * security keys land in the multisite-aware global_settings store. The
 * legacy SecuritySetting model is preserved as the type contract for
 * existing callsites; runtime values come from SettingResolver.
 */
class SecuritySettingRepository extends AbstractSettingRepository implements SecuritySettingRepositoryInterface
{
    public function __construct(
        private readonly SettingResolver $resolver,
        private readonly SettingDefinitionRegistry $registry,
    ) {
        $this->cachePrefix = 'security_setting:';
        $this->cacheAllKey = 'security_settings_all';
        $this->cacheTtl = 10;
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelClass(): string
    {
        return SecuritySetting::class;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $value = $this->resolver->get($name);

        return $value ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $name, mixed $value): Model
    {
        $this->resolver->set($name, $value);
        $this->clearCache($name);

        return GlobalSetting::query()->where('name', $name)->first()
            ?? new GlobalSetting(['name' => $name]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $result = [];
        foreach ($this->registry->all() as $name => $_definition) {
            $result[$name] = $this->resolver->get($name);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        try {
            return $this->resolver->get($name) !== null;
        } catch (UnknownSettingException) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $name): bool
    {
        try {
            $this->resolver->delete($name);
            $this->clearCache($name);

            return true;
        } catch (UnknownSettingException) {
            return false;
        }
    }
}
