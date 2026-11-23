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

declare(strict_types=1);

namespace Tests\Unit\View;

use App\Helpers\CaptchaHelper;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CaptchaPreviewRenderTest extends TestCase
{
    private const WIDGET = '<div class="cf-turnstile" data-sitekey="test"></div>';

    public function test_the_widget_is_drawn_on_an_ordinary_render(): void
    {
        $html = Blade::render(
            '<x-captcha :enabled="true" :widget="$widget" />',
            ['widget' => self::WIDGET],
        );

        $this->assertStringContainsString('cf-turnstile', $html);
    }

    public function test_a_preview_render_draws_no_widget(): void
    {
        CaptchaHelper::markPreviewRender();

        $html = Blade::render(
            '<x-captcha :enabled="true" :widget="$widget" />',
            ['widget' => self::WIDGET],
        );

        $this->assertStringNotContainsString('cf-turnstile', $html);
    }

    /**
     * The marker is for rendering only. Verification must never read it, or a
     * form drawn under a preview route could be submitted with the CAPTCHA
     * skipped -- and several preview routes do submit.
     */
    public function test_the_marker_does_not_change_whether_a_form_is_verified(): void
    {
        $before = CaptchaHelper::shouldShowCaptcha('admin_login');

        CaptchaHelper::markPreviewRender();

        $this->assertSame(
            $before,
            CaptchaHelper::shouldShowCaptcha('admin_login'),
            'markPreviewRender() must not reach the verification gate.',
        );
    }

    public function test_the_marker_is_off_until_it_is_set(): void
    {
        $this->assertFalse(CaptchaHelper::isPreviewRender());
    }
}
