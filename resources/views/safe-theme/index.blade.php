{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

{{--
Safe Theme Index - Minimal front page for safe mode
--}}

@extends('themes::layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-3xl mx-auto">
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-6">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-paint-brush text-purple-600 dark:text-purple-400 text-xl mt-0.5"></i>
                    <div>
                        <h2 class="text-lg font-semibold text-purple-800 dark:text-purple-200">
                            {{ __('admin/safe-mode.theme_safe_mode_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-purple-700 dark:text-purple-300">
                            {{ __('admin/safe-mode.theme_safe_mode_front_message') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
