<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace Tests\Unit;

use App\DTO\Extension\ReleaseInfo;
use PHPUnit\Framework\TestCase;

class ReleaseInfoTest extends TestCase
{
    public function test_basic_construction(): void
    {
        $info = new ReleaseInfo(
            version: '1.2.3',
            slug: 'test-plugin',
            extensionType: 'plugin',
            downloadUrl: 'https://example.com/plugin.zip',
            changelog: 'Bug fixes',
        );

        $this->assertEquals('1.2.3', $info->version);
        $this->assertEquals('test-plugin', $info->slug);
        $this->assertEquals('plugin', $info->extensionType);
        $this->assertEquals('https://example.com/plugin.zip', $info->downloadUrl);
        $this->assertEquals('Bug fixes', $info->changelog);
    }

    public function test_from_git_hub_with_zip_asset(): void
    {
        $response = [
            'tag_name' => 'v2.0.0',
            'body' => 'Major update',
            'published_at' => '2026-03-01T00:00:00Z',
            'id' => 12345,
            'prerelease' => false,
            'zipball_url' => 'https://api.github.com/repos/Org/repo/zipball/v2.0.0',
            'assets' => [
                [
                    'name' => 'test-plugin-2.0.0.zip',
                    'browser_download_url' => 'https://github.com/Org/repo/releases/download/v2.0.0/test-plugin-2.0.0.zip',
                ],
            ],
        ];

        $info = ReleaseInfo::fromGitHub($response, 'test-plugin');

        $this->assertEquals('2.0.0', $info->version);
        $this->assertEquals('test-plugin', $info->slug);
        $this->assertEquals('plugin', $info->extensionType);
        $this->assertEquals(
            'https://github.com/Org/repo/releases/download/v2.0.0/test-plugin-2.0.0.zip',
            $info->downloadUrl
        );
        $this->assertEquals('Major update', $info->changelog);
        $this->assertEquals(12345, $info->metadata['github_release_id']);
        $this->assertFalse($info->metadata['prerelease']);
    }

    public function test_from_git_hub_falls_back_to_zipball_url(): void
    {
        $response = [
            'tag_name' => 'v1.0.0',
            'zipball_url' => 'https://api.github.com/repos/Org/repo/zipball/v1.0.0',
            'assets' => [],
        ];

        $info = ReleaseInfo::fromGitHub($response, 'my-plugin');

        $this->assertEquals('1.0.0', $info->version);
        $this->assertEquals(
            'https://api.github.com/repos/Org/repo/zipball/v1.0.0',
            $info->downloadUrl
        );
    }

    public function test_from_git_hub_strips_version_prefix(): void
    {
        $response = [
            'tag_name' => 'v3.1.4',
            'assets' => [],
        ];

        $info = ReleaseInfo::fromGitHub($response, 'test');
        $this->assertEquals('3.1.4', $info->version);
    }

    public function test_to_array(): void
    {
        $info = new ReleaseInfo(
            version: '1.0.0',
            slug: 'test',
            extensionType: 'theme',
            metadata: ['key' => 'value'],
        );

        $array = $info->toArray();

        $this->assertEquals('1.0.0', $array['version']);
        $this->assertEquals('test', $array['slug']);
        $this->assertEquals('theme', $array['extension_type']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
    }

    public function test_json_serialize(): void
    {
        $info = new ReleaseInfo(
            version: '1.0.0',
            slug: 'test',
            extensionType: 'plugin',
        );

        $json = json_encode($info);
        $decoded = json_decode($json, true);

        $this->assertEquals('1.0.0', $decoded['version']);
        $this->assertEquals('test', $decoded['slug']);
    }
}
