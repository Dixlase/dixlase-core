{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

Dual-licensed under the GNU Affero General Public License v3 or later
(together with the Dixlase Plugin and Theme Exception) or a commercial
license from exc-D inc. See LICENSE for details.
--}}

{{--
    404 error — page not found.

    Delegates to the active theme's branded template
    (`themes::errors.404`) when one is available; falls back to the
    minimal core HTML when no theme is active, the theme has no
    error template, or the theme is broken in a way that prevents
    `View::exists()` from confirming the override.
--}}
@if(\Illuminate\Support\Facades\View::exists('themes::errors.404'))
    @include('themes::errors.404')
@else
    @include('errors._minimal', [
        'code' => '404',
        'title' => app()->getLocale() === 'ja' ? 'ページが見つかりません' : 'Page Not Found',
        'message' => app()->getLocale() === 'ja'
            ? 'お探しのページは存在しないか、移動した可能性があります。'
            : 'The page you were looking for does not exist or may have been moved.',
    ])
@endif
