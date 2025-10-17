{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

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

@props(['widget' => null, 'enabled' => false])

@if($enabled && $widget)
    <div class="captcha-container flex justify-center items-center">
        {!! $widget !!}
    </div>
    
    <!-- CAPTCHA エラー表示 -->
    @error('captcha')
        <div class="text-red-600 text-sm mt-1">
            {{ $message }}
        </div>
    @enderror
@endif
