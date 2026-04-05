{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-ui-pagination-controls />

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
    'paginator' => null, // ページネーターオブジェクト
    'perPageOptions' => [10, 25, 50, 100], // 表示件数オプション
    'currentPerPage' => 25, // 現在の表示件数
    'totalLabel' => 'components/ui-pagination.total_count', // 総件数ラベルの翻訳キー
    'perPageLabel' => 'components/ui-pagination.per_page_label', // 表示件数ラベルの翻訳キー
    'sortOptions' => [], // ソートオプション ['name' => '名前', 'created_at' => '作成日時']
    'currentSort' => 'created_at', // 現在のソートフィールド
    'currentOrder' => 'desc', // 現在のソート順序 (asc/desc)
    'showSort' => false, // ソート機能を表示するか
])

<div x-data="paginationControls()" class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <!-- 総件数表示（左側） -->
    <div class="text-sm text-gray-700 dark:text-gray-300">
        @if($paginator)
            @php
                $total = method_exists($paginator, 'total') ? $paginator->total() : ($paginator->total ?? 0);
            @endphp
            {{ __($totalLabel, ['total' => $total]) }}
        @endif
    </div>
    
    <!-- コントロール（右側） -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <!-- ソート選択 -->
        @if($showSort && !empty($sortOptions))
            <div class="flex items-center gap-2">
                <label for="sortBy" class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                    {{ __('components/ui-pagination.sort_by') }}:
                </label>
                <select id="sortBy" 
                        name="sort" 
                        class="px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    @foreach($sortOptions as $field => $label)
                        <option value="{{ $field }}" {{ $currentSort === $field ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                
                <!-- ソート順序ボタン -->
                <button type="button" 
                        id="sortOrder" 
                        data-order="{{ $currentOrder }}"
                        class="px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                        title="{{ $currentOrder === 'asc' ? __('components/ui-pagination.ascending') : __('components/ui-pagination.descending') }}">
                    <i class="fas fa-sort-amount-{{ $currentOrder === 'asc' ? 'up' : 'down' }}-alt"></i>
                    <span class="ml-1 text-sm">{{ $currentOrder === 'asc' ? __('components/ui-pagination.asc') : __('components/ui-pagination.desc') }}</span>
                </button>
            </div>
        @endif
        
        <!-- 表示件数選択 -->
        <div class="flex items-center gap-2">
            <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                {{ __($perPageLabel) }}:
            </label>
            <select id="perPage" 
                    name="per_page" 
                    class="px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                @foreach($perPageOptions as $count)
                    <option value="{{ $count }}" {{ $currentPerPage == $count ? 'selected' : '' }}>
                        {{ $count }}{{ __('components/ui-pagination.items_suffix') }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
