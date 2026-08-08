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
Standalone dashboard notice shown only when a core, plugin, or theme update is
available. Counts come from DashboardPresenter::extensionOverview()['updates']
(passed in as $updates); the whole card links to the integrated update
management page. Renders nothing when everything is up to date.
--}}
@if(($updates['total'] ?? 0) > 0)
    <section class="overflow-hidden shadow-sm sm:rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-800/60 dark:text-blue-200">
                    <i class="fas fa-arrow-up"></i>
                </span>
                <div>
                    <h2 class="text-base font-semibold text-blue-900 dark:text-blue-100">
                        {{ __('admin/dashboard.updates_available_label') }}
                        <span class="ml-1 inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-blue-600 px-2 py-0.5 text-xs font-bold text-white">
                            {{ $updates['total'] }}
                        </span>
                    </h2>
                    <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">
                        {{ __('admin/dashboard.updates_available_summary', [
                            'core' => $updates['core'] ?? 0,
                            'plugins' => $updates['plugins'] ?? 0,
                            'themes' => $updates['themes'] ?? 0,
                        ]) }}
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.settings.systems.updates.index') }}"
               class="inline-flex flex-shrink-0 items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                {{ __('admin/dashboard.updates_manage_link') }}
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>
@endif
