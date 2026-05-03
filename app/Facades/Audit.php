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

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Audit Facade — convenience accessor for AuditService.
 * Plugins/themes may use this facade to log auditable events
 * (auth, security, content, plugin lifecycle, etc.).
 *
 * @method static \App\Models\AuditLog|null log(array $data)
 * @method static \App\Models\AuditLog|null logAuth(string $action, array $data = [])
 * @method static \App\Models\AuditLog|null logSecurity(string $action, array $data = [])
 * @method static \App\Models\AuditLog|null logExtension(string $action, array $data = [])
 * @method static \App\Models\AuditLog|null logAccount(string $action, array $data = [])
 * @method static \App\Models\AuditLog|null logSystem(string $action, array $data = [])
 * @method static \App\Models\AuditLog|null logContent(string $action, array $data = [])
 * @method static \App\Models\AuditLog|null logPlugin(string $action, array $data = [])
 * @method static \App\Models\AuditLog|null logAi(string $action, array $data = [])
 * @method static \App\Models\AuditLog|null logBulkSettingsChange(string $settingsPage, array $before, array $after, ?\Illuminate\Database\Eloquent\Model $actor = null, array $sensitiveKeys = [])
 * @method static array buildAiContext(string $reason, ?string $intent = null, array $extra = [])
 * @method static \App\Services\AuditService setActorSource(?string $source)
 * @method static string|null getActorSource()
 * @method static string getRequestId()
 * @method static \App\Services\AuditService setRequestId(string $requestId)
 * @method static \App\Services\AuditService setPluginContext(?string $pluginName, ?string $version = null)
 * @method static \App\Services\AuditService clearPluginContext()
 * @method static \App\Services\AuditService setImpersonatedBy(?int $memberId)
 * @method static array diff(array $before, array $after)
 * @method static \App\Models\AuditLog|null logModelChange(\Illuminate\Database\Eloquent\Model $model, string $action, ?\Illuminate\Database\Eloquent\Model $actor = null, ?array $additionalContext = null)
 * @method static \App\Models\AuditLog|null logSettingsChange(string $settingKey, mixed $oldValue, mixed $newValue, ?\Illuminate\Database\Eloquent\Model $actor = null, ?string $pluginName = null)
 * @method static \Illuminate\Database\Eloquent\Collection getLogsForActor(\Illuminate\Database\Eloquent\Model $actor, int $limit = 50)
 * @method static \Illuminate\Database\Eloquent\Collection getLogsForTarget(\Illuminate\Database\Eloquent\Model $target, int $limit = 50)
 * @method static \Illuminate\Database\Eloquent\Collection getLogsForRequest(string $requestId)
 * @method static \Illuminate\Database\Eloquent\Collection getRecentWarnings(int $hours = 24, int $limit = 100)
 * @method static \Illuminate\Database\Eloquent\Collection getRecentFailures(int $hours = 24, int $limit = 100)
 *
 * @see \App\Services\AuditService
 */
class Audit extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'audit';
    }
}
