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

@props([
    'size' => 'xs', // xs, sm
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 ml-2 text-' . $size . ' font-medium rounded-md bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200']) }}>
    {{ __('common.required') }}
</span>
