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

<div class="mt-4">
    <label for="{{ $id }}" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
        {{ $label }}
    </label>
    
    <input id="{{ $id }}" 
           type="{{ $type }}" 
           name="{{ $name }}" 
           value="{{ $value }}" 
           {{ $required ? 'required' : '' }}
           {{ $autofocus ? 'autofocus' : '' }}
           autocomplete="{{ $autocomplete }}"
           class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:bg-gray-800 dark:border-gray-500 dark:text-white dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
    
    @if($errors->has($name))
        <ul class="text-sm text-red-600 dark:text-red-400 space-y-1 mt-1">
            @foreach ($errors->get($name) as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</div>
