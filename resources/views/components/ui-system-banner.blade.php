{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-ui-system-banner />

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

@foreach($banners as $banner)
<div class="{{ $banner['bgClass'] }} text-white px-4 py-3 shadow-md ui-system-banner"
     role="alert">
    <div class="max-w-full mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <i class="{{ $banner['icon'] }} text-xl"></i>
            <div>
                @if($banner['title'] !== '')
                    <div class="font-semibold">{{ $banner['title'] }}</div>
                @endif
                @if($banner['message'] !== '')
                    <div class="text-sm opacity-90">{{ $banner['message'] }}</div>
                @endif
            </div>
        </div>
        @if(! empty($banner['actions']))
            <div class="flex items-center space-x-2">
                @foreach($banner['actions'] as $action)
                    @if($action['method'] === 'POST')
                        <form method="POST" action="{{ $action['url'] }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 {{ $action['btnClass'] }} rounded transition text-sm font-medium whitespace-nowrap">
                                {{ $action['label'] }}
                            </button>
                        </form>
                    @else
                        <a href="{{ $action['url'] }}"
                           class="px-4 py-2 {{ $action['btnClass'] }} rounded transition text-sm font-medium whitespace-nowrap">
                            {{ $action['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
@endforeach
