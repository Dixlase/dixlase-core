{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-ui-admin-demo-banner />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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

{{--
Demo mode banner for admin panel (sticky display when DIXLASE_DEMO_MODE is on)
--}}
@if(config('dixlase.demo_mode'))
<div id="admin-demo-banner" class="bg-blue-600 dark:bg-blue-700 text-white px-4 py-2 shadow-md">
    <div class="max-w-full mx-auto flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-x-2">
            <i class="fas fa-flask text-base" aria-hidden="true"></i>
            {{-- Title and the details link share one line to keep the banner short
                 on narrow mobile screens. The full explanation opens in the modal
                 below (the banner itself carries no long copy). --}}
            <span class="font-semibold">{{ __('admin/demo.banner_title') }}</span>
            <a href="#" id="admin-demo-banner-details" class="text-sm underline opacity-90 hover:no-underline whitespace-nowrap">
                <i class="fas fa-circle-info mr-1" aria-hidden="true"></i>{{ __('admin/demo.details_link') }}
            </a>
        </div>
        {{-- Access hints: how to sign in and where the tenant's own front page
             and admin URL live. URLs resolve to the current (tenant) host. --}}
        <div class="text-sm flex flex-wrap items-center gap-x-4 gap-y-1">
            {{-- Order: front page and admin URL first (where to go), then the
                 account and expiry (session details). --}}
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="underline hover:no-underline whitespace-nowrap"><i class="fas fa-external-link-alt mr-1" aria-hidden="true"></i>{{ __('admin/demo.front_page') }}</a>
            <a href="{{ route('admin.login') }}" target="_blank" rel="noopener" class="underline hover:no-underline whitespace-nowrap"><i class="fas fa-user-shield mr-1" aria-hidden="true"></i>{{ __('admin/demo.admin_url') }}</a>
            @if(config('dixlase.demo_account'))
                <span><i class="fas fa-user mr-1" aria-hidden="true"></i>{{ __('admin/demo.account') }}: <strong>{{ config('dixlase.demo_account') }}</strong></span>
            @endif
            @if(config('dixlase.demo_expires_at'))
                {{-- Rendered as an ISO-8601 UTC timestamp; the script below
                     rewrites it to each visitor's local time. The " UTC"
                     fallback text is what shows if JavaScript is disabled. --}}
                <span class="whitespace-nowrap"><i class="fas fa-clock mr-1" aria-hidden="true"></i>{{ __('admin/demo.expires') }}: <time data-demo-localtime datetime="{{ \Illuminate\Support\Carbon::parse(config('dixlase.demo_expires_at'))->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse(config('dixlase.demo_expires_at'))->isoFormat('M/D HH:mm') }} UTC</time></span>
            @endif
        </div>
    </div>
</div>

{{-- Details modal: the full demo-mode explanation, kept out of the banner so the
     banner stays compact on mobile. Centered + close-only. Works on both the
     admin layout and the login screen, since the common bundle (which registers
     the modal() Alpine component and window.openModal) is loaded in both. --}}
<x-ui-modal
    id="demoDetailsModal"
    centered
    close-only
    icon-type="info"
    :close-label="__('common.close')"
    :title="__('admin/demo.details_title')"
    message=""
>
    {{-- The modal body is centered by default (.modal-body is text-center), so
         wrap the explanation in a left-aligned bullet list: one point per line
         is easier to scan than a centered paragraph. --}}
    <div class="text-left text-sm">
        <p class="mb-3">{{ __('admin/demo.details_intro') }}</p>
        <ul class="list-disc list-outside pl-5 space-y-2">
            @foreach ((array) __('admin/demo.details_points') as $point)
                <li>{{ $point }}</li>
            @endforeach
        </ul>
    </div>
</x-ui-modal>

{{-- Localise the expiry <time> to the visitor's own timezone, and open the
     details modal from the compact banner link. Server time is UTC; visitors
     are worldwide, so the correct wall-clock time is only known client-side.
     Nonce'd per CSP; no-JS falls back to the UTC text above. --}}
<script @cspNonce>
    (function () {
        document.querySelectorAll('time[data-demo-localtime]').forEach(function (el) {
            var d = new Date(el.getAttribute('datetime'));
            if (!isNaN(d.getTime())) {
                el.textContent = d.toLocaleString([], {
                    month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit'
                });
            }
        });
        var detailsLink = document.getElementById('admin-demo-banner-details');
        if (detailsLink) {
            detailsLink.addEventListener('click', function (e) {
                e.preventDefault();
                if (typeof window.openModal === 'function') {
                    window.openModal('demoDetailsModal');
                }
            });
        }
    })();
</script>
@endif
