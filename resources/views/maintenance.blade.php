{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@php
    $isAdmin = $isAdmin ?? false;
    $appearance = $appearance ?? '0';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      @if($isAdmin) x-data="appearanceMode('{{ $appearance }}')" x-init="init()" :class="{ 'dark': isDark, 'light': !isDark, 'theme-ready': themeReady }" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if($isAdmin)
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script @cspNonce>
            (function(){
                var a='{{ $appearance }}';
                var d=a==='2'||(a==='0'&&window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.add(d?'dark':'light');
            })();
        </script>
        <style @cspNonce>
            :root { --admin-banner-offset: 0px; }
            #admin-bar { top: var(--admin-banner-offset, 0px); }
        </style>
    @endif
    <title>{{ __('maintenance.title') }}</title>
    <link rel="stylesheet" href="/assets/css/maintenance.css">
    @if($isAdmin)
        {!! load_active_assets() !!}
        <x-ui-notification />
    @endif
</head>
<body @class(['admin maintenance-admin font-sans antialiased' => $isAdmin])
      @if($isAdmin)
          data-default-appearance="{{ $appearance }}"
          x-data="adminLayout()"
          x-init="init()"
      @endif>
    @if($isAdmin)
        {{-- 管理画面バナースタック（メンテナンス / セーフモード / システム警告） --}}
        <div id="admin-banner-stack"
             class="fixed top-0 left-0 right-0 z-[9999] flex flex-col"
             x-data
             x-init="
                 const root = document.documentElement;
                 const update = () => {
                     const h = $el.offsetHeight || 0;
                     root.style.setProperty('--admin-banner-offset', h + 'px');
                 };
                 update();
                 const ro = new ResizeObserver(update);
                 ro.observe($el);
                 window.addEventListener('resize', update);
             ">
            <x-ui-admin-maintenance-banner />
            <x-security.safe-mode-banner />
            <x-ui-system-banner />
        </div>

        <x-ui-admin-bar :isAdminLayout="true" />
    @endif

    <div class="maintenance-page">
        <div class="container">
            @if(isset($isPreview) && $isPreview)
                <div class="preview-badge">
                    {{ __('maintenance.preview_mode') }}
                </div>
            @endif

            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/>
                </svg>
            </div>

            <h1>{{ __('maintenance.title') }}</h1>

            <div class="maintenance-message">{{ $message }}</div>

            @if(isset($releaseAt) && $releaseAt)
                <div class="release-info">
                    <p>{{ __('maintenance.expected_release') }}</p>
                    <div class="release-time">
                        {{ \Carbon\Carbon::parse($releaseAt)->format('Y年m月d日 H:i') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
