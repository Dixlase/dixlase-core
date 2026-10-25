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

namespace App\Console\Traits;

use App\Models\AuditLog;
use App\Models\Plugin;
use App\Models\Theme;
use App\Services\AuditService;
use App\Services\Extension\ExtensionAuditScope;
use Illuminate\Support\Facades\Log;

/**
 * Records a step command -- migrate, migration rollback, seed, build -- in
 * the audit log (dixlase-core#749).
 *
 * These run an extension's own code (migrations, seeders, npm scripts) but
 * are not lifecycle operations, so they are written straight to the audit
 * log: no `extension_log_operations` gate and no notification mail, unlike
 * RecordsExtensionOperation. Nothing in the admin panel runs them on their
 * own, so no actor is recorded; the row's actor_source says where the step
 * ran (`cli`, or `web` when the install wizard builds a theme).
 *
 * Inside an operation that records itself (ExtensionAuditScope) nothing is
 * written: the install, update or rollback row already covers the step.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
trait RecordsExtensionStep
{
    /**
     * Record a successful step. Never throws: the step has already run, so
     * a failure to record it is logged, not raised.
     *
     * @param  'plugin'|'theme'  $type
     * @param  string  $directory  The extension's directory name, which is what the step commands take
     * @param  array<string, mixed>  $context
     */
    protected function recordExtensionStep(string $action, string $type, string $directory, array $context = [], string $severity = AuditLog::SEVERITY_NOTICE): void
    {
        if (ExtensionAuditScope::active()) {
            return;
        }

        try {
            $extension = $type === 'plugin'
                ? Plugin::where('directory', $directory)->first()
                : Theme::where('directory', $directory)->first();
            $slug = $extension !== null ? $extension->slug : null;

            app(AuditService::class)->logExtension($action, [
                'severity' => $severity,
                'target' => $extension,
                'target_label' => $type.' '.($slug ?? $directory),
                'context' => [
                    $type.'_slug' => $slug,
                    'directory' => $directory,
                ] + $context,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record an extension step', [
                'action' => $action,
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
