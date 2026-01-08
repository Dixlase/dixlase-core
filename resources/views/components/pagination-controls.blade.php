@props([
    'paginator' => null, // ページネーターオブジェクト
    'perPageOptions' => [10, 25, 50, 100], // 表示件数オプション
    'currentPerPage' => 25, // 現在の表示件数
    'totalLabel' => 'components.pagination.total_count', // 総件数ラベルの翻訳キー
    'perPageLabel' => 'components.pagination.per_page_label', // 表示件数ラベルの翻訳キー
    'sortOptions' => [], // ソートオプション ['name' => '名前', 'created_at' => '作成日時']
    'currentSort' => 'created_at', // 現在のソートフィールド
    'currentOrder' => 'desc', // 現在のソート順序 (asc/desc)
    'showSort' => false, // ソート機能を表示するか
])

<div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
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
                    {{ __('components.pagination.sort_by') }}:
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
                        title="{{ $currentOrder === 'asc' ? __('components.pagination.ascending') : __('components.pagination.descending') }}">
                    <i class="fas fa-sort-amount-{{ $currentOrder === 'asc' ? 'up' : 'down' }}-alt"></i>
                    <span class="ml-1 text-sm">{{ $currentOrder === 'asc' ? __('components.pagination.asc') : __('components.pagination.desc') }}</span>
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
                        {{ $count }}{{ __('components.pagination.items_suffix') }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

@push('scripts')
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    const perPageSelect = document.getElementById('perPage');
    const sortBySelect = document.getElementById('sortBy');
    const sortOrderButton = document.getElementById('sortOrder');
    
    // 表示件数変更
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('per_page', this.value);
            currentUrl.searchParams.delete('page'); // ページ番号をリセット
            
            window.location.href = currentUrl.toString();
        });
    }
    
    // ソートフィールド変更
    if (sortBySelect) {
        sortBySelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', this.value);
            currentUrl.searchParams.delete('page'); // ページ番号をリセット
            
            window.location.href = currentUrl.toString();
        });
    }
    
    // ソート順序変更
    if (sortOrderButton) {
        sortOrderButton.addEventListener('click', function() {
            const currentOrder = this.getAttribute('data-order');
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('order', newOrder);
            currentUrl.searchParams.delete('page'); // ページ番号をリセット
            
            window.location.href = currentUrl.toString();
        });
    }
});
</script>
@endpush
