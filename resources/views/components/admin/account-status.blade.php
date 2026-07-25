{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-admin.account-status />

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

@props([
    'statusValue' => null, // ステータス値（コントローラーから渡す）
    'statusOptions' => [], // ステータスオプション（コントローラーから渡す、必須）
    'columns' => 2, // カラム数（コントローラーから渡す）
    'legendLabel' => null, // 凡例ラベル（コントローラーから渡す）
    'isInitialAdmin' => false,
    'showDescriptions' => false,
    'descriptions' => [], // ステータス説明配列（コントローラーから渡す）
    'errors' => null,
])

<fieldset>
    <legend>
        {{ $legendLabel }}
        @if($isInitialAdmin)
            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">（初期管理者のため変更不可）</span>
        @endif
    </legend>
    
    @if($isInitialAdmin)
        <input type="hidden" name="status" value="1">
        <p class="description-text">{{ __('admin/members/form.initial_admin_status_fixed') }}</p>
    @else
        <x-form-radio-card-group
            name="status"
            :options="$statusOptions"
            :value="$statusValue"
            :columns="$columns"
            class="mb-3"
        />
        
        @if($showDescriptions && !empty($descriptions))
            {{-- Status description --}}
            <div class="my-4 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                @foreach($descriptions as $description)
                    <div class="flex items-start space-x-2">
                        <i class="{{ $description['icon'] }} {{ $description['iconColor'] }} mt-0.5"></i>
                        <div>
                            <strong class="text-gray-900 dark:text-white">{{ $description['label'] }}:</strong>
                            <span>{{ $description['text'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
    
    <x-form-error
        :messages="$errors?->get('status') ?? []"
    />
</fieldset>
