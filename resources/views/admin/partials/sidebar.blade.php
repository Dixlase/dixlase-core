{{--
This file is part of Your Software Name.

Copyright (C) 2024 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

<ul class="nav flex-column">
    @foreach (config('admin.nav') as $key => $item)
        <li class="nav-item">
            <a href="{{ route($item['route']) }}" class="nav-link">
                <i class="{{ $item['icon'] }}"></i>
                <span>{{ __($item['text']) }}</span>
            </a>

            @if (isset($item['master']))
                <ul class="nav flex-column ml-3">
                    <li class="nav-item">
                        <a href="{{ route($item['master']['route']) }}" class="nav-link">
                            <span>{{ __($item['text']) }}</span>
                        </a>
                    </li>
                </ul>
            @endif
        </li>
    @endforeach
</ul>
