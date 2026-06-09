{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-error />

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

@props(['name' => null, 'messages' => null])

@php
    $errorMessages = $messages ?? ($name ? $errors->get($name) : []);
@endphp

@if ($errorMessages && count($errorMessages) > 0)
    <ul {{ $attributes->merge(['class' => 'text-sm my-2 p-2 bg-red-100 border border-red-200 rounded-lg text-red-600 dark:text-red-400' ]) }}>
        @foreach ((array) $errorMessages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
