{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-content.scheduler-notice />

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
"The scheduler is not running" notice for file-stored content

Shown where a publish mode for file-stored content is set (#746). Without the
scheduler, a change made to the files is found only when a page or an edit
screen that shows the record is read, so the dashboard count and the mail can
lag behind the files.

Props:
- $running: whether the scheduler is running; pass
  FileContentPublisher::schedulerRunning(). Nothing is shown when true
--}}

@props([
    'running' => true,
])

@if (! $running)
    <div {{ $attributes->merge(['class' => 'mt-4 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-700 dark:bg-amber-950/40']) }}
        role="note">
        <i class="fas fa-clock mt-0.5 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
        <div>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('common.file_changes.scheduler_stopped_title') }}</p>
            <p class="mt-1 text-gray-700 dark:text-gray-300">{{ __('common.file_changes.scheduler_stopped_message') }}</p>
        </div>
    </div>
@endif
