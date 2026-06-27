{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dual-licensed under the GNU Affero General Public License v3 or later
(together with the Dixlase Plugin and Theme Exception) or a commercial
license from exc-D inc. See LICENSE for details.
--}}

{{--
    419 error — page expired (CSRF token mismatch / session timeout).

    Common on form submits after the user left a page open beyond
    the session lifetime. Delegates to `themes::errors.419` when
    available; falls back to the minimal core HTML otherwise.
--}}
@if(\Illuminate\Support\Facades\View::exists('themes::errors.419'))
    @include('themes::errors.419')
@else
    @include('errors._minimal', [
        'code' => '419',
        'title' => app()->getLocale() === 'ja' ? 'ページの有効期限が切れました' : 'Page Expired',
        'message' => app()->getLocale() === 'ja'
            ? 'セッションの有効期限が切れました。ページを再読み込みしてもう一度お試しください。'
            : 'Your session has expired. Please refresh the page and try again.',
    ])
@endif
