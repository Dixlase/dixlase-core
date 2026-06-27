{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dual-licensed under the GNU Affero General Public License v3 or later
(together with the Dixlase Plugin and Theme Exception) or a commercial
license from exc-D inc. See LICENSE for details.
--}}

{{--
    403 error — forbidden.

    Delegates to `themes::errors.403` when available; falls back to
    the minimal core HTML otherwise.
--}}
@if(\Illuminate\Support\Facades\View::exists('themes::errors.403'))
    @include('themes::errors.403')
@else
    @include('errors._minimal', [
        'code' => '403',
        'title' => app()->getLocale() === 'ja' ? 'アクセスが拒否されました' : 'Access Denied',
        'message' => app()->getLocale() === 'ja'
            ? 'このページを表示する権限がありません。'
            : 'You do not have permission to access this page.',
    ])
@endif
