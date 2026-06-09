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

@foreach($activeModes as $mode)
<div class="{{ $mode['bgClass'] }} text-white px-4 py-3 shadow-md safe-mode-banner"
     role="alert"
     id="safe-mode-banner-{{ $mode['value'] }}">
    <div class="max-w-full mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <i class="{{ $mode['iconClass'] }} text-xl"></i>
            <div>
                <div class="font-semibold">
                    {{ __($mode['translationPrefix'] . '_banner_title') }}
                </div>
                <div class="text-sm opacity-90">
                    {{ __($mode['translationPrefix'] . '_banner_message') }}
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route($mode['settingsRoute']) }}"
               class="px-4 py-2 bg-white {{ $mode['linkTextClass'] }} rounded hover:bg-gray-100 transition text-sm font-medium whitespace-nowrap">
                {{ __($mode['translationPrefix'] . '_go_to_settings') }}
            </a>
            <form method="POST" action="{{ route('admin.safe-mode.disable') }}" class="inline safe-mode-banner-form">
                @csrf
                <input type="hidden" name="mode" value="{{ $mode['value'] }}">
                <button type="submit"
                        class="px-4 py-2 {{ $mode['buttonClass'] }} text-white rounded transition text-sm font-medium whitespace-nowrap">
                    {{ __('admin/safe-mode.disable') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endforeach
