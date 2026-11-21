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

namespace Tests\Unit\View;

use Tests\TestCase;

/**
 * The admin sidebar's shape is decided twice, and the two have to agree.
 *
 * The inline script in layouts/admin.blade.php runs before the first paint,
 * because a class on <html> is the only part of the shape that cannot wait
 * for Alpine. layout.js decides the same thing afterwards. Both pick a band
 * from the viewport width, and the band decides which stored key is read --
 * and which one the tab button writes.
 *
 * So a mismatch is not a cosmetic difference: between the two numbers, the
 * choice is written under one key and read back under another. That is #1083,
 * where the inline script said 1280 while layout.js said 1024, so a viewer
 * between those widths who picked the full menu got the rail on the next load
 * and then watched it animate open.
 *
 * Nothing can share the constants at runtime: the inline script has to run
 * before any module is fetched. These tests stand in for that.
 */
class AdminSidebarBreakpointsTest extends TestCase
{
    private function layoutBlade(): string
    {
        return file_get_contents(resource_path('views/layouts/admin.blade.php'));
    }

    private function layoutJs(): string
    {
        return file_get_contents(resource_path('src/admin/js/layout.js'));
    }

    /**
     * The two width comparisons in the inline script, in source order.
     *
     * @return array{0: int, 1: int} the desktop minimum, then the tablet one
     */
    private function inlineScriptBreakpoints(): array
    {
        $matched = preg_match_all('/w\s*>=\s*(\d+)/', $this->layoutBlade(), $matches);

        $this->assertSame(
            2,
            $matched,
            'The anti-FOUC inline script in layouts/admin.blade.php should compare the width twice, '
            .'once per band. Found '.$matched.'. If the script was restructured, update this test '
            .'with it -- do not delete it: the breakpoints it pins are duplicated by necessity.'
        );

        return [(int) $matches[1][0], (int) $matches[1][1]];
    }

    private function jsConstant(string $name): int
    {
        $this->assertSame(
            1,
            preg_match('/const\s+'.preg_quote($name, '/').'\s*=\s*(\d+);/', $this->layoutJs(), $m),
            $name.' should be declared exactly once in resources/src/admin/js/layout.js.'
        );

        return (int) $m[1];
    }

    public function test_the_inline_script_and_layout_js_agree_on_the_desktop_band(): void
    {
        [$inlineDesktop] = $this->inlineScriptBreakpoints();

        $this->assertSame(
            $this->jsConstant('SIDEBAR_DESKTOP_MIN'),
            $inlineDesktop,
            'The inline script in layouts/admin.blade.php and SIDEBAR_DESKTOP_MIN in layout.js must '
            .'name the same width. Between two different values, the tab button stores the chosen '
            .'shape under one key and the next page load reads another one (#1083).'
        );
    }

    public function test_the_inline_script_and_layout_js_agree_on_the_tablet_band(): void
    {
        [, $inlineTablet] = $this->inlineScriptBreakpoints();

        $this->assertSame(
            $this->jsConstant('SIDEBAR_TABLET_MIN'),
            $inlineTablet,
            'The inline script in layouts/admin.blade.php and SIDEBAR_TABLET_MIN in layout.js must '
            .'name the same width. Below it the mobile drawer owns the sidebar and neither class is '
            .'set, so a difference here paints a rail over the drawer band.'
        );
    }

    public function test_the_inline_script_reads_the_key_each_band_writes(): void
    {
        $blade = $this->layoutBlade();
        $js = $this->layoutJs();

        foreach (['adminSidebarMode', 'adminSidebarTabletMode', 'sidebarCollapsed'] as $key) {
            $this->assertStringContainsString(
                "'".$key."'",
                $blade,
                'The inline script should read '.$key.', which layout.js writes.'
            );
            $this->assertStringContainsString(
                "'".$key."'",
                $js,
                $key.' should still be the key layout.js uses; the inline script reads it.'
            );
        }
    }

    public function test_the_class_names_the_stylesheet_owns_are_the_ones_the_scripts_set(): void
    {
        $scss = file_get_contents(resource_path('src/admin/scss/_sidebar-modes.scss'));
        $js = $this->layoutJs();

        foreach (['dls-sidebar-rail-active', 'dls-sidebar-switching', 'dls-sidebar-no-motion'] as $class) {
            $this->assertStringContainsString(
                $class,
                $js,
                $class.' should be set by layout.js; the stylesheet does nothing on its own.'
            );
            $this->assertStringContainsString(
                'html.'.$class,
                $scss,
                $class.' should be styled in _sidebar-modes.scss; a class nothing styles is a no-op.'
            );
        }

        $this->assertStringContainsString(
            'dls-sidebar-rail-active',
            $this->layoutBlade(),
            'The inline script should add the rail class itself, before the first paint.'
        );
    }

    public function test_a_correction_at_startup_is_applied_without_motion(): void
    {
        $js = $this->layoutJs();

        $this->assertStringContainsString(
            'applySidebarModeWithoutMotion()',
            $js,
            'layout.js should keep the silent path for correcting the shape at startup (#1083).'
        );

        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*this\.sidebarRailActive\(\)\s*!==\s*document\.documentElement\.classList\.contains\(\s*SIDEBAR_RAIL_CLASS\s*\)\s*\)/',
            $js,
            'init() should compare the computed shape with the class the inline script already set, '
            .'and only correct it when they differ. Applying it unconditionally through the silent '
            .'path would also silence the first deliberate switch.'
        );
    }
}
