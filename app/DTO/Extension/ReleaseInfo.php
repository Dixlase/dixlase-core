<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\DTO\Extension;

use JsonSerializable;

/**
 * Release information DTO returned by extension source providers
 */
final readonly class ReleaseInfo implements JsonSerializable
{
    public function __construct(
        public string $version,
        public string $slug,
        public string $extensionType,
        public ?string $downloadUrl = null,
        public ?string $changelog = null,
        public ?string $publishedAt = null,
        public ?string $minimumPhpVersion = null,
        public ?string $minimumLaravelVersion = null,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}

    /**
     * Create from GitHub API release response
     *
     * @param  array<string, mixed>  $response
     */
    public static function fromGitHub(array $response, string $slug, string $extensionType = 'plugin'): self
    {
        $version = ltrim($response['tag_name'] ?? '', 'v');
        $zipAsset = collect($response['assets'] ?? [])->first(
            fn (array $asset) => str_ends_with($asset['name'], '.zip')
        );

        return new self(
            version: $version,
            slug: $slug,
            extensionType: $extensionType,
            downloadUrl: $zipAsset['browser_download_url'] ?? $response['zipball_url'] ?? null,
            changelog: $response['body'] ?? null,
            publishedAt: $response['published_at'] ?? null,
            metadata: [
                'github_release_id' => $response['id'] ?? null,
                'tag_name' => $response['tag_name'] ?? null,
                'prerelease' => $response['prerelease'] ?? false,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'slug' => $this->slug,
            'extension_type' => $this->extensionType,
            'download_url' => $this->downloadUrl,
            'changelog' => $this->changelog,
            'published_at' => $this->publishedAt,
            'minimum_php_version' => $this->minimumPhpVersion,
            'minimum_laravel_version' => $this->minimumLaravelVersion,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
