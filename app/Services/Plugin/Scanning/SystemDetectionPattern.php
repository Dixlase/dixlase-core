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
 * システム関連の検出パターン
 *
 * system.register_shortcodes, system.register_commands,
 * system.register_blade_directives, system.modify_routes を検出します。
 */
class SystemDetectionPattern extends DetectionPattern
{
    public function __construct(
        protected string $subKey = 'register_shortcodes',
    ) {}

    public function permissionKey(): string
    {
        return "system.{$this->subKey}";
    }

    public function filePatterns(): array
    {
        return match ($this->subKey) {
            'register_shortcodes' => ['app/Shortcodes/*.php'],
            'register_commands' => ['app/Console/Commands/*.php', 'app/Console/*.php'],
            default => [],
        };
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'register_shortcodes' => [
                '/PluginHelper::registerShortcode/i',
                '/ThemeHelper::registerShortcode/i',
                '/app\s*\(\s*[\'"]shortcode[\'"]\s*\)/i',
            ],
            'register_commands' => [
                '/\$this->commands\s*\(/i',
                '/Artisan::command/i',
            ],
            'register_blade_directives' => [
                '/Blade::directive\s*\(/i',
                '/Blade::if\s*\(/i',
                '/Blade::component\s*\(/i',
            ],
            'modify_routes' => [
                '/Route::macro/i',
                '/Router::macro/i',
            ],
            default => [],
        };
    }
}
