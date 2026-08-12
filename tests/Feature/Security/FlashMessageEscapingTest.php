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

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

/**
 * The flash-message component rendered session values with `{!! !!}`. Around 78
 * call sites across the admin controllers push an exception message into a
 * flash:
 *
 *     ->with('error', __('...upload_failed', ['error' => $e->getMessage()]))
 *
 * Exception text carries strings the operator supplied -- a URL typed into
 * "add from URL", an uploaded file name, a ZIP entry name -- and __() does not
 * escape its replacements. So user-influenced text reached the DOM unescaped.
 *
 * A handful of flashes genuinely need markup (the "just added" CTA link, the
 * update-complete rollback hint), which is why the component could not simply
 * be switched to `{{ }}` and left there. Blade's `{{ }}` escapes strings but
 * passes Htmlable through untouched, so those builders now return HtmlString
 * and say out loud that they mean HTML; everything else is escaped by default.
 */
class FlashMessageEscapingTest extends TestCase
{
    /**
     * $errors normally arrives from the ShareErrorsFromSession middleware,
     * which Blade::render() does not run, so it is supplied explicitly.
     */
    private function renderComponent(?\Illuminate\Support\ViewErrorBag $errors = null): string
    {
        // Shared rather than passed: the component has its own scope, so data
        // handed to Blade::render() stops at the outer template. Sharing is
        // also what ShareErrorsFromSession does in a real request.
        View::share('errors', $errors ?? new \Illuminate\Support\ViewErrorBag());

        return Blade::render('<x-ui-flash-message />');
    }

    private function renderFlash(string $key, mixed $value): string
    {
        session()->flash($key, $value);

        return $this->renderComponent();
    }

    /**
     * @return list<array{0: string}>
     */
    public static function flashKeys(): array
    {
        return [
            'status' => ['status'],
            'success' => ['success'],
            'error' => ['error'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('flashKeys')]
    public function test_plain_string_flashes_are_escaped(string $key): void
    {
        $html = $this->renderFlash($key, '<img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString(
            '<img src=x onerror=alert(1)>',
            $html,
            "A plain-string {$key} flash must be escaped: exception messages routinely carry operator-supplied text."
        );
        $this->assertStringContainsString('&lt;img', $html, 'The text should still be visible, just inert.');
    }

    /**
     * The escaping must not cost the flashes that legitimately carry links.
     */
    public function test_htmlable_flashes_still_render_markup(): void
    {
        $html = $this->renderFlash('success', new HtmlString('done <a href="/x">open</a>'));

        $this->assertStringContainsString(
            '<a href="/x">open</a>',
            $html,
            'A flash that opts in by returning HtmlString must keep rendering its markup.'
        );
    }

    /**
     * Validation errors land in the same component and are equally
     * user-influenced.
     */
    public function test_validation_errors_are_escaped(): void
    {
        $messages = new \Illuminate\Support\MessageBag(['field' => ['<script>alert(1)</script>']]);
        $bag = new \Illuminate\Support\ViewErrorBag();
        $bag->put('default', $messages);

        $html = $this->renderComponent($bag);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html, 'The message should still be shown, escaped.');
    }

    /**
     * Guards the component itself: reintroducing `{!! !!}` here would undo all
     * of the above in one edit.
     */
    public function test_component_does_not_use_unescaped_output(): void
    {
        $view = file_get_contents(base_path('resources/views/components/ui-flash-message.blade.php'));

        $this->assertStringNotContainsString(
            '{!!',
            $view,
            'The flash component must render escaped; flashes needing markup pass an HtmlString instead.'
        );
    }
}
