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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Services\Privacy;

use App\Contracts\PluginIntegration\PrivacyDataProviderInterface;
use App\DTO\PluginPrivacy\UserDataExportDTO;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\PluginServiceResolver;
use App\Services\Site\SiteStorage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Aggregates every PrivacyDataProviderInterface implementation registered
 * in the container and writes their exported data into a single ZIP
 * archive that can be downloaded by an operator.
 *
 * The archive layout is:
 *
 *   manifest.json                       <- summary of providers, scope, errors
 *   {providerKey}/data.json             <- DTO::$data, JSON-encoded
 *   {providerKey}/files/{relative}      <- entries from DTO::$files
 *
 * Provider failures (capability unavailable, plugin permission denied,
 * exception during export) are recorded in the manifest rather than
 * aborting the whole export. Operators can review the manifest to
 * determine whether a re-run with adjusted permissions is needed.
 */
class UserPrivacyExporter
{
    public function __construct(
        private readonly PluginServiceResolver $resolver,
        private readonly PluginPermissionService $permissions,
    ) {}

    /**
     * Build a privacy export ZIP for the given user.
     *
     * @param  int  $userId  Subject identifier passed to each provider.
     * @param  int|null  $siteId  null = network-wide; int = data tied to that site only.
     * @return string Absolute filesystem path of the resulting ZIP archive.
     */
    public function exportToZip(int $userId, ?int $siteId = null): string
    {
        $zipPath = $this->newZipPath($userId, $siteId);

        $zip = new ZipArchive();
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new RuntimeException(
                "Failed to open ZIP archive for writing: {$zipPath} (code {$opened})"
            );
        }

        $manifest = [
            'user_id' => $userId,
            'site_id' => $siteId,
            'scope' => $siteId === null ? 'network' : 'site',
            'exported_at' => Carbon::now()->toIso8601String(),
            'providers' => [],
            'unresolved' => [],
            'errors' => [],
        ];

        foreach ($this->resolver->resolveAll(PrivacyDataProviderInterface::class) as $result) {
            if (! $result->isResolved()) {
                $manifest['unresolved'][] = [
                    'plugin_slug' => $result->pluginSlug,
                    'failure_reason' => $result->failureReason,
                    'denied_permission' => $result->deniedPermission,
                ];

                continue;
            }

            $provider = $result->instance;
            if (! $provider instanceof PrivacyDataProviderInterface) {
                continue;
            }

            $slug = $provider->getPluginSlug();

            if (! $this->isExportAllowed($slug)) {
                $manifest['unresolved'][] = [
                    'plugin_slug' => $slug,
                    'failure_reason' => 'permission_denied',
                    'denied_permission' => 'privacy.export',
                ];

                continue;
            }

            try {
                $dto = $provider->exportUserData($userId, $siteId);
            } catch (Throwable $e) {
                Log::error('Privacy provider failed to export user data', [
                    'plugin' => $slug,
                    'provider_key' => $provider->privacyProviderKey(),
                    'user_id' => $userId,
                    'site_id' => $siteId,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
                $manifest['errors'][] = [
                    'plugin_slug' => $slug,
                    'provider_key' => $provider->privacyProviderKey(),
                    'message' => $e->getMessage(),
                ];

                continue;
            }

            $this->writeProviderToZip($zip, $dto);

            $manifest['providers'][] = [
                'provider_key' => $dto->providerKey,
                'plugin_slug' => $slug,
                'description' => $provider->privacyDataDescription(),
                'is_empty' => $dto->isEmpty(),
                'file_count' => count($dto->files),
                'warnings' => $dto->warnings,
            ];
        }

        $zip->addFromString(
            'manifest.json',
            (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        $zip->close();

        return $zipPath;
    }

    /**
     * Decide whether the given plugin slug is allowed to participate in
     * an export. Core providers (slug 'core' or prefixed 'core-') are
     * always allowed because they do not have a plugin.json to declare
     * permissions. Plugin providers must declare 'privacy.export'.
     */
    private function isExportAllowed(string $slug): bool
    {
        if ($slug === 'core' || str_starts_with($slug, 'core-')) {
            return true;
        }

        return $this->permissions->check($slug, 'privacy.export');
    }

    /**
     * Write a provider's DTO contents into the ZIP archive.
     */
    private function writeProviderToZip(ZipArchive $zip, UserDataExportDTO $dto): void
    {
        $providerKey = $dto->providerKey;

        $zip->addFromString(
            "{$providerKey}/data.json",
            (string) json_encode($dto->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        foreach ($dto->files as $relative => $absolute) {
            if (! is_string($absolute) || ! is_file($absolute)) {
                continue;
            }
            $zip->addFile($absolute, "{$providerKey}/files/{$relative}");
        }
    }

    /**
     * Compute the destination path for a fresh export ZIP.
     *
     * Network-wide exports go under storage/app/private/global/privacy-exports.
     * Site-scoped exports go under storage/app/private/sites/{siteId}/privacy-exports.
     */
    private function newZipPath(int $userId, ?int $siteId): string
    {
        $base = $siteId === null
            ? SiteStorage::globalPath()
            : SiteStorage::privatePath($siteId);

        $directory = $base.'/privacy-exports';
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Failed to create privacy export directory: {$directory}");
        }

        $scope = $siteId === null ? 'network' : "site{$siteId}";
        $timestamp = Carbon::now()->format('Ymd-His');

        return "{$directory}/privacy-export-user{$userId}-{$scope}-{$timestamp}.zip";
    }
}
