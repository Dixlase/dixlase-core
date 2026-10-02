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

namespace App\Services\Extension;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\PluginVersionHistory;
use App\Models\Theme;
use App\Models\ThemeVersionHistory;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * Records plugin and theme updates and rollbacks: an audit-log entry and a
 * version-history row, plus the signing-key / author-id change events.
 *
 * Called from dls:{plugin,theme}:update and dls:{plugin,theme}:rollback, so
 * every entry point -- the admin updates screen, the rollback buttons, the
 * CLI, dls:extensions:update -- records exactly once. The recording used to
 * live in admin controller actions only, and after those were removed no
 * update or rollback left a trace (dixlase-core#454).
 *
 * Never throws: a failed write must not turn a finished update into a
 * failed one, or hide the error that failed it.
 */
class ExtensionUpdateRecorder
{
    /**
     * The state of an extension before the operation, for the history row.
     *
     * @return array{version: ?string, signing_key_id: ?string, author_id: ?string}
     */
    public static function snapshot(Plugin|Theme $extension): array
    {
        return [
            'version' => $extension->version,
            'signing_key_id' => $extension->signing_key_id,
            'author_id' => $extension->author_id,
        ];
    }

    /**
     * Record a finished update or rollback.
     *
     * Re-reads the supply-chain fields from the new manifest first, so the
     * history row compares the signing key and author id of the code that
     * was replaced with those of the code now on disk.
     *
     * @param  'update'|'rollback'  $operation
     * @param  array{version: ?string, signing_key_id: ?string, author_id: ?string}  $before
     * @param  array<string, mixed>  $context
     */
    public static function succeeded(string $operation, Plugin|Theme $extension, array $before, ?int $appliedById, array $context = []): void
    {
        try {
            self::refreshSupplyChainFields($extension);

            $old = $before['signing_key_id'];
            $new = $extension->signing_key_id;
            $oldAuthor = $before['author_id'];
            $newAuthor = $extension->author_id;
            $signingKeyChanged = $old !== null && $old !== $new;
            $authorChanged = $oldAuthor !== null && $oldAuthor !== $newAuthor;

            $isPlugin = $extension instanceof Plugin;
            $historyClass = $isPlugin ? PluginVersionHistory::class : ThemeVersionHistory::class;
            $historyClass::create([
                ($isPlugin ? 'plugin_slug' : 'theme_slug') => $extension->slug,
                'old_version' => $before['version'],
                'new_version' => $extension->version,
                'old_signing_key_id' => $old,
                'new_signing_key_id' => $new,
                'old_author_id' => $oldAuthor,
                'new_author_id' => $newAuthor,
                'files_changed_count' => 0,
                'lines_added' => 0,
                'lines_removed' => 0,
                'signing_key_changed' => $signingKeyChanged,
                'author_id_changed' => $authorChanged,
                'installation_method' => $operation === 'rollback'
                    ? $historyClass::METHOD_ROLLBACK
                    : $historyClass::METHOD_UPDATE,
                'installed_from_url' => $extension->installed_from_url,
                'applied_by_id' => $appliedById,
                'applied_at' => now(),
            ]);

            self::audit(self::action($extension, $operation, true), $extension, $before['version'], $extension->version, $appliedById, true, $context);

            if ($signingKeyChanged) {
                self::audit($isPlugin ? AuditLog::ACTION_PLUGIN_SIGNING_KEY_CHANGED : AuditLog::ACTION_THEME_SIGNING_KEY_CHANGED,
                    $extension, $before['version'], $extension->version, $appliedById, true,
                    ['old_signing_key_id' => $old, 'new_signing_key_id' => $new], AuditLog::SEVERITY_WARNING);
            }
            if ($authorChanged) {
                self::audit($isPlugin ? AuditLog::ACTION_PLUGIN_AUTHOR_ID_CHANGED : AuditLog::ACTION_THEME_AUTHOR_ID_CHANGED,
                    $extension, $before['version'], $extension->version, $appliedById, true,
                    ['old_author_id' => $oldAuthor, 'new_author_id' => $newAuthor], AuditLog::SEVERITY_WARNING);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to record an extension update', [
                'extension' => $extension->slug,
                'operation' => $operation,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record a failed update or rollback. No version-history row: nothing
     * changed version.
     *
     * @param  'update'|'rollback'  $operation
     * @param  array<string, mixed>  $context
     */
    public static function failed(string $operation, Plugin|Theme $extension, ?string $from, ?string $to, ?int $appliedById, string $reason, array $context = []): void
    {
        try {
            self::audit(self::action($extension, $operation, false), $extension, $from, $to, $appliedById, false, ['error' => $reason] + $context);
        } catch (\Throwable $e) {
            Log::warning('Failed to record a failed extension update', [
                'extension' => $extension->slug,
                'operation' => $operation,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Copy author_id / authority_key_id / signing.key_id from the manifest on
     * disk into the row. The admin install path does the same through
     * persistSupplyChainMetadata(); an update or rollback replaces the
     * manifest, so the row has to follow it.
     */
    private static function refreshSupplyChainFields(Plugin|Theme $extension): void
    {
        $manifest = $extension instanceof Plugin
            ? base_path("plugins/{$extension->directory}/plugin.json")
            : base_path("themes/{$extension->directory}/theme.json");
        if (! is_file($manifest)) {
            return;
        }

        $data = json_decode((string) file_get_contents($manifest), true);
        if (! is_array($data)) {
            return;
        }

        $extension->update([
            'author_id' => $data['author_id'] ?? null,
            'authority_key_id' => $data['authority_key_id'] ?? null,
            'signing_key_id' => $data['signing']['key_id'] ?? null,
        ]);
    }

    private static function action(Plugin|Theme $extension, string $operation, bool $succeeded): string
    {
        $plugin = $extension instanceof Plugin;

        return match (true) {
            $operation === 'update' && $succeeded => $plugin ? AuditLog::ACTION_PLUGIN_UPDATED : AuditLog::ACTION_THEME_UPDATED,
            $operation === 'update' => $plugin ? AuditLog::ACTION_PLUGIN_UPDATE_FAILED : AuditLog::ACTION_THEME_UPDATE_FAILED,
            $succeeded => $plugin ? AuditLog::ACTION_PLUGIN_ROLLED_BACK : AuditLog::ACTION_THEME_ROLLED_BACK,
            default => $plugin ? AuditLog::ACTION_PLUGIN_ROLLBACK_FAILED : AuditLog::ACTION_THEME_ROLLBACK_FAILED,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function audit(string $action, Plugin|Theme $extension, ?string $from, ?string $to, ?int $appliedById, bool $succeeded, array $context, ?string $severity = null): void
    {
        $kind = $extension instanceof Plugin ? 'plugin' : 'theme';

        app(AuditService::class)->logExtension($action, [
            'severity' => $severity ?? ($succeeded ? AuditLog::SEVERITY_NOTICE : AuditLog::SEVERITY_ERROR),
            'outcome' => $succeeded ? AuditLog::OUTCOME_SUCCESS : AuditLog::OUTCOME_FAILURE,
            'actor' => $appliedById !== null ? Member::find($appliedById) : null,
            'target' => $extension,
            'target_label' => sprintf('%s %s v%s → v%s', $kind, $extension->slug, $from ?? '?', $to ?? '?'),
            'context' => [
                $kind.'_slug' => $extension->slug,
                'from' => $from,
                'to' => $to,
                'applied_by_id' => $appliedById,
            ] + $context,
        ]);
    }
}
