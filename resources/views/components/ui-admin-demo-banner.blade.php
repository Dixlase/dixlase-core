{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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
<div id="admin-demo-banner" class="bg-blue-600 dark:bg-blue-700 text-white px-4 py-3 shadow-md">
    <div class="max-w-full mx-auto flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center space-x-3">
            <i class="fas fa-flask text-xl"></i>
            <div>
                <div class="font-semibold">
                    {{ __('admin/demo.banner_title') }}
                </div>
                <div class="text-sm opacity-90">
                    {{ __('admin/demo.banner_message') }}
                </div>
            </div>
        </div>
        {{-- Access hints: how to sign in and where the tenant's own front page
             and admin URL live. URLs resolve to the current (tenant) host. --}}
        <div class="text-sm flex flex-wrap items-center gap-x-4 gap-y-1">
            @if(config('dixlase.demo_account'))
                <span><i class="fas fa-user mr-1" aria-hidden="true"></i>{{ __('admin/demo.account') }}: <strong>{{ config('dixlase.demo_account') }}</strong></span>
            @endif
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="underline hover:no-underline whitespace-nowrap"><i class="fas fa-external-link-alt mr-1" aria-hidden="true"></i>{{ __('admin/demo.front_page') }}</a>
            <span class="whitespace-nowrap"><i class="fas fa-user-shield mr-1" aria-hidden="true"></i>{{ __('admin/demo.admin_url') }}: {{ route('admin.login') }}</span>
            @if(config('dixlase.demo_expires_at'))
                <span class="whitespace-nowrap"><i class="fas fa-clock mr-1" aria-hidden="true"></i>{{ __('admin/demo.expires') }}: {{ \Illuminate\Support\Carbon::parse(config('dixlase.demo_expires_at'))->isoFormat('M/D HH:mm') }}</span>
            @endif
        </div>
    </div>
</div>
@endif
