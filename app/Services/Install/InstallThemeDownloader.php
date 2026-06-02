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

namespace App\Services\Install;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

/**
 * Fetches a first-party theme's latest GitHub release ZIP and extracts it into
 * themes/. Used only by the install wizard when the user's release tarball did
 * not bundle the default theme. Driven by config('themes.downloadable').
 */
class InstallThemeDownloader
{
    /**
     * Return the list of downloadable themes that are not yet present locally.
     *
     * @return array<int, array{directory: string, repository: string, label: string}>
     */
    public function missingThemes(): array
    {
        $missing = [];
        foreach ((array) config('themes.downloadable', []) as $entry) {
            if (! isset($entry['directory'], $entry['repository'], $entry['label'])) {
                continue;
            }
            $path = base_path('themes/'.$entry['directory']);
            $hasThemeJson = is_file($path.'/theme.json');
            if (! $hasThemeJson) {
                $missing[] = $entry;
            }
        }

        return $missing;
    }

    /**
     * Look up the downloadable theme entry by its target directory name.
     */
    public function find(string $directory): ?array
    {
        foreach ((array) config('themes.downloadable', []) as $entry) {
            if (($entry['directory'] ?? null) === $directory) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Download the latest GitHub release ZIP for the given theme and extract
     * it into themes/<directory>.
     *
     * @throws \RuntimeException on any failure
     */
    public function download(string $directory): void
    {
        $entry = $this->find($directory);
        if ($entry === null) {
            throw new \RuntimeException("Theme not registered as downloadable: {$directory}");
        }

        $repo = $entry['repository'];
        $zipUrl = $this->resolveZipUrl($repo);

        $tmpZip = tempnam(sys_get_temp_dir(), 'dixlase-theme-').'.zip';
        $tmpDir = sys_get_temp_dir().'/dixlase-theme-'.bin2hex(random_bytes(8));

        try {
            $request = Http::withOptions(['sink' => $tmpZip])
                ->timeout(120)
                ->withHeaders(['User-Agent' => 'Dixlase-Installer']);

            // First-party themes may live in a private repository during
            // development. github.com's archive endpoint silently returns 404
            // to anonymous requests against private repos (it intentionally
            // hides their existence), so we forward the operator-configured
            // GitHub PAT when one is available. The token comes from
            // EXTENSION_GITHUB_TOKEN (config('extension-sources.github.default_token'));
            // env() inside the config requires `php artisan config:clear`
            // after the .env edit.
            $token = $this->githubToken();
            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->get($zipUrl);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Download failed (HTTP {$response->status()}): {$zipUrl}"
                );
            }

            File::ensureDirectoryExists($tmpDir, 0775);

            $zip = new ZipArchive();
            if ($zip->open($tmpZip) !== true) {
                throw new \RuntimeException("Failed to open downloaded ZIP: {$tmpZip}");
            }
            $zip->extractTo($tmpDir);
            $zip->close();

            // GitHub release ZIPs nest everything under <repo>-<sha>/ — find that
            // top-level directory so we can move its contents into themes/.
            $entries = array_values(array_filter(
                scandir($tmpDir),
                fn ($e) => $e !== '.' && $e !== '..'
            ));
            if (count($entries) !== 1 || ! is_dir($tmpDir.'/'.$entries[0])) {
                throw new \RuntimeException('Unexpected ZIP layout: a single top-level directory was expected');
            }
            $extracted = $tmpDir.'/'.$entries[0];

            if (! is_file($extracted.'/theme.json')) {
                throw new \RuntimeException('Downloaded archive does not contain a theme.json');
            }

            $targetPath = base_path('themes/'.$directory);
            if (is_dir($targetPath)) {
                File::deleteDirectory($targetPath);
            }
            File::ensureDirectoryExists(dirname($targetPath), 0775);

            // File::moveDirectory() only calls rename(), which fails with
            // EXDEV across filesystems. In a Docker setup the temp dir
            // (sys_get_temp_dir() — typically /tmp on the container's
            // overlay fs) and themes/ (bind-mounted from the host) live
            // on different filesystems, so rename() silently fails and
            // returns false. Copy the tree instead; the temp dir is
            // unlinked in the finally block below.
            if (! File::copyDirectory($extracted, $targetPath)) {
                throw new \RuntimeException("Failed to copy theme into themes/{$directory}");
            }
        } finally {
            if (is_file($tmpZip)) {
                @unlink($tmpZip);
            }
            if (is_dir($tmpDir)) {
                File::deleteDirectory($tmpDir);
            }
        }
    }

    /**
     * Resolve the ZIP URL of the latest GitHub release for the given repo.
     * Falls back to the main-branch tarball when the repo has no releases yet.
     */
    private function resolveZipUrl(string $repo): string
    {
        $apiUrl = "https://api.github.com/repos/{$repo}/releases/latest";
        $request = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'Dixlase-Installer',
            ]);

        // Same auth path as the archive download below. Without this,
        // the API returns 404 for private repos and we fall straight to
        // the branch tarball URL — which then also 404s anonymously.
        $token = $this->githubToken();
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->get($apiUrl);

        if ($response->successful()) {
            $tag = $response->json('tag_name');
            if (is_string($tag) && $tag !== '') {
                return "https://github.com/{$repo}/archive/refs/tags/{$tag}.zip";
            }
        }

        // No tagged release yet → fall back to the main branch ZIP so a
        // brand-new theme repository is still usable.
        return "https://github.com/{$repo}/archive/refs/heads/main.zip";
    }

    /**
     * Resolve the GitHub PAT to authenticate downloads of private theme
     * repositories. Reads the operator-configured token from
     * extension-sources config (EXTENSION_GITHUB_TOKEN env). Returns an
     * empty string when no token is configured — in which case the
     * downloader falls back to anonymous requests, which work for
     * public repos and 404 on private ones.
     */
    private function githubToken(): string
    {
        $token = config('extension-sources.github.default_token');

        return is_string($token) ? trim($token) : '';
    }
}
