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

namespace Tests\Feature\Admin\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * かんたんモード用バナーコンポーネントのレンダリングテスト
 */
class AdminModeBannerComponentTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // ReadOnly バナー
    // ========================================

    public function test_readonly_banner_renders_with_default_message(): void
    {
        $view = $this->blade('<x-admin.mode-readonly-banner />');

        $view->assertSee(__('components/admin/mode-readonly-banner.message'));
        $view->assertSee(__('components/admin/mode-readonly-banner.hint'));
        $view->assertSee(__('components/admin/mode-readonly-banner.switch_mode'));
        $view->assertSee('fa-lock');
        $view->assertSee(route('admin.settings.base.mode'));
    }

    public function test_readonly_banner_renders_with_custom_message(): void
    {
        $view = $this->blade('<x-admin.mode-readonly-banner message="Custom readonly text" />');

        $view->assertSee('Custom readonly text');
        $view->assertDontSee(__('components/admin/mode-readonly-banner.message'));
    }

    // ========================================
    // GuideOnly バナー
    // ========================================

    public function test_guide_banner_renders_with_default_message(): void
    {
        $view = $this->blade('<x-admin.mode-guide-banner />');

        $view->assertSee(__('components/admin/mode-guide-banner.message'));
        $view->assertSee(__('components/admin/mode-guide-banner.hint'));
        $view->assertSee(__('components/admin/mode-guide-banner.switch_mode'));
        $view->assertSee('fa-directions');
        $view->assertSee(route('admin.settings.base.mode'));
    }

    public function test_guide_banner_renders_with_custom_message(): void
    {
        $view = $this->blade('<x-admin.mode-guide-banner message="Custom guide text" />');

        $view->assertSee('Custom guide text');
        $view->assertDontSee(__('components/admin/mode-guide-banner.message'));
    }

    // ========================================
    // Partial ノーティス
    // ========================================

    public function test_partial_notice_renders_with_default_message(): void
    {
        $view = $this->blade('<x-admin.mode-partial-notice />');

        $view->assertSee(__('components/admin/mode-partial-notice.message'));
        $view->assertSee('fa-magic');
    }

    public function test_partial_notice_renders_with_custom_message(): void
    {
        $view = $this->blade('<x-admin.mode-partial-notice message="Custom partial text" />');

        $view->assertSee('Custom partial text');
        $view->assertDontSee(__('components/admin/mode-partial-notice.message'));
    }
}
