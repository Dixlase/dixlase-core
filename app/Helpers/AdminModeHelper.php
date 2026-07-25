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

namespace App\Helpers;

use App\Enums\AdminMode;
use App\Enums\MenuVisibility;
use App\Services\Site\SettingResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 */
class AdminModeHelper
{
    private static ?AdminMode $currentMode = null;

    private static ?array $visibilities = null;

    /**
     * Get the current admin panel mode
     */
    public static function getCurrentMode(): AdminMode
    {
        if (self::$currentMode !== null) {
            return self::$currentMode;
        }

        try {
            // admin_mode is Global scope; SettingResolver routes through
            // global_settings. Keep the schema guard so installation flows
            // (sites table absent) fall through to the default.
            if (! Schema::hasTable('global_settings')) {
                self::$currentMode = AdminMode::default();

                return self::$currentMode;
            }

            $value = app(SettingResolver::class)->get('admin_mode');

            self::$currentMode = AdminMode::fromInt($value !== null ? (int) $value : null);
        } catch (\Throwable $e) {
            Log::warning('Failed to retrieve admin_mode: '.$e->getMessage());
            self::$currentMode = AdminMode::default();
        }

        return self::$currentMode;
    }

    /**
     * Whether it is simple mode
     */
    public static function isSimpleMode(): bool
    {
        return self::getCurrentMode()->isSimple();
    }

    /**
     * Whether it is detailed mode
     */
    public static function isAdvancedMode(): bool
    {
        return self::getCurrentMode()->isAdvanced();
    }

    /**
     * Get menu visibility settings
     */
    public static function getVisibilities(): array
    {
        if (self::$visibilities !== null) {
            return self::$visibilities;
        }

        // In detailed mode, everything is Full
        if (self::isAdvancedMode()) {
            self::$visibilities = [];

            return self::$visibilities;
        }

        // Get default settings
        $defaults = config('admin.mode.simple_defaults', []);
        $defaultValues = [];
        foreach ($defaults as $key => $vis) {
            $defaultValues[$key] = $vis instanceof MenuVisibility ? $vis->value : (int) $vis;
        }

        // Get saved settings (admin_mode_visibilities is
        // automatically decoded to array by Resolver since type=array in Global scope)
        try {
            if (Schema::hasTable('global_settings')) {
                $saved = app(SettingResolver::class)->get('admin_mode_visibilities');

                if (is_array($saved)) {
                    // Override defaults with saved settings
                    self::$visibilities = array_merge($defaultValues, $saved);

                    return self::$visibilities;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to retrieve admin_mode_visibilities: '.$e->getMessage());
        }

        self::$visibilities = $defaultValues;

        return self::$visibilities;
    }

    /**
     * Get the visibility level of the specified menu key
     *
     * @param  string  $menuKey  Menu key in dot notation (e.g., 'settings.security')
     */
    public static function getMenuVisibility(string $menuKey): MenuVisibility
    {
        // In detailed mode, everything is Full
        if (self::isAdvancedMode()) {
            return MenuVisibility::Full;
        }

        $visibilities = self::getVisibilities();

        if (isset($visibilities[$menuKey])) {
            return MenuVisibility::fromInt((int) $visibilities[$menuKey]);
        }

        // Inherit parent key settings
        $parts = explode('.', $menuKey);
        while (count($parts) > 1) {
            array_pop($parts);
            $parentKey = implode('.', $parts);
            if (isset($visibilities[$parentKey])) {
                return MenuVisibility::fromInt((int) $visibilities[$parentKey]);
            }
        }

        // Top-level key
        if (isset($visibilities[$parts[0]])) {
            return MenuVisibility::fromInt((int) $visibilities[$parts[0]]);
        }

        return MenuVisibility::Full;
    }

    /**
     * Whether the menu is visible (not Hidden)
     */
    public static function isMenuVisible(string $menuKey): bool
    {
        return self::getMenuVisibility($menuKey) !== MenuVisibility::Hidden;
    }

    /**
     * Whether the menu is editable (Full or Partial)
     */
    public static function isMenuEditable(string $menuKey): bool
    {
        $vis = self::getMenuVisibility($menuKey);

        return $vis === MenuVisibility::Full || $vis === MenuVisibility::Partial;
    }

    /**
     * Whether the menu is read-only
     */
    public static function isMenuReadOnly(string $menuKey): bool
    {
        return self::getMenuVisibility($menuKey) === MenuVisibility::ReadOnly;
    }

    /**
     * Whether the menu is navigation-only
     */
    public static function isMenuGuideOnly(string $menuKey): bool
    {
        return self::getMenuVisibility($menuKey) === MenuVisibility::GuideOnly;
    }

    /**
     * Get all mode-related data for views
     *
     * Returns a collection of mode data to pass from controller to view
     * Return keys:
     *   - isSimpleMode: whether simple mode is enabled
     *   - visibility: MenuVisibility enum value (int)
     *   - isEditable: whether editable (Full/Partial)
     *   - isReadOnly: whether read-only
     *   - isGuideOnly: whether guide-only
     *   - isPartial: whether Partial mode (some fields restricted)
     *
     * @param  string  $menuKey  Menu key in dot notation
     * @return array{isSimpleMode: bool, visibility: int, isEditable: bool, isReadOnly: bool, isGuideOnly: bool, isPartial: bool}
     */
    public static function getViewModeData(string $menuKey): array
    {
        $visibility = self::getMenuVisibility($menuKey);

        return [
            'isSimpleMode' => self::isSimpleMode(),
            'visibility' => $visibility->value,
            'isEditable' => $visibility === MenuVisibility::Full || $visibility === MenuVisibility::Partial,
            'isReadOnly' => $visibility === MenuVisibility::ReadOnly,
            'isGuideOnly' => $visibility === MenuVisibility::GuideOnly,
            'isPartial' => $visibility === MenuVisibility::Partial,
        ];
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        self::$currentMode = null;
        self::$visibilities = null;
    }
}
