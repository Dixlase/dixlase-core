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
    <div class="mb-6 p-4 font-semibold text-blue-800 bg-blue-100 border border-blue-200 rounded-xl">
        {{ session('status') }}
    </div>
@endif

@if (session('success'))
    <div class="mb-6 p-4 font-semibold text-green-800 bg-green-100 border border-green-200 rounded-xl">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 p-4 font-semibold text-red-800 bg-red-100 border border-red-200 rounded-xl">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
