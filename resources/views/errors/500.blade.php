{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

Dual-licensed under the GNU Affero General Public License v3 or later
(together with the Dixlase Plugin and Theme Exception) or a commercial
license from exc-D inc. See LICENSE for details.
--}}

{{--
    500 error — server error.

    Intentionally does NOT delegate to the active theme. A 500
    typically means *something* in the request path failed, and
    the active theme is one of the candidates — attempting to
    render `themes::errors.500` via the theme's layout / partials
    / SCSS pipeline could re-trigger the original exception and
    spiral into a render loop. The minimal core fallback is
    self-contained (inline CSS, no DB, no theme deps) and renders
    reliably regardless of what else is broken.
--}}
@include('errors._minimal', [
    'code' => '500',
    'title' => app()->getLocale() === 'ja' ? 'サーバーエラー' : 'Server Error',
    'message' => app()->getLocale() === 'ja'
        ? 'サーバーで問題が発生しました。しばらくしてからもう一度お試しください。'
        : 'Something went wrong on the server. Please try again later.',
])
