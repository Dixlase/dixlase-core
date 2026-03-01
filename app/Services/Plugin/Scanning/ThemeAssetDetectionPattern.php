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

namespace App\Services\Plugin\Scanning;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * テーマアセット関連の検出パターン（テーマ専用）
 *
 * assets.custom_css, assets.custom_js, assets.external_resources を検出します。
 */
class ThemeAssetDetectionPattern extends DetectionPattern
{
    public function __construct(
        protected string $subKey = 'custom_css',
    ) {}

    public function permissionKey(): string
    {
        return "assets.{$this->subKey}";
    }

    public function applicableTo(): string
    {
        return 'theme';
    }

    public function filePatterns(): array
    {
        return match ($this->subKey) {
            'custom_css' => [
                'resources/src/css/*.css',
                'resources/src/scss/*.scss',
                'resources/assets/css/*.css',
            ],
            'custom_js' => [
                'resources/src/js/*.js',
                'resources/assets/js/*.js',
            ],
            default => [],
        };
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'external_resources' => [
                '/https?:\/\/[^\s\'"]+\.(js|css)/i',
                '/<script[^>]+src=[\'"]https?:\/\//i',
                '/<link[^>]+href=[\'"]https?:\/\//i',
                '/import\s+.*from\s+[\'"]https?:\/\//i',
            ],
            default => [],
        };
    }
}
