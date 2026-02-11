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

@if (session('status'))
    <div class="my-4 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 text-sm"></i>
            </div>
            <div class="ml-2 flex-1 font-medium">
                {!! session('status') !!}
            </div>
        </div>
    </div>
@endif

@if (session('success'))
    <div class="my-4 p-4 rounded-lg bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-500 dark:text-green-400 text-sm"></i>
            </div>
            <div class="ml-2 flex-1 font-bold">
                {!! session('success') !!}
            </div>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="my-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-times-circle text-red-500 dark:text-red-400 text-sm"></i>
            </div>
            <div class="ml-2 flex-1 font-medium">
                {!! session('error') !!}
            </div>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="my-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-times-circle text-red-500 dark:text-red-400 text-sm"></i>
            </div>
            <div class="ml-2 flex-1 font-medium">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{!! $error !!}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
