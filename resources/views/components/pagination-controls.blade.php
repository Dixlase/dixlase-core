@props([
    'paginator' => null, // ページネーターオブジェクト
    'perPageOptions' => [10, 25, 50, 100], // 表示件数オプション
    'currentPerPage' => 25, // 現在の表示件数
    'totalLabel' => 'components.pagination.total_count', // 総件数ラベルの翻訳キー
    'perPageLabel' => 'components.pagination.per_page_label', // 表示件数ラベルの翻訳キー
])

<div class="mb-4 flex justify-between items-center">
    <!-- 総件数表示（左側） -->
    <div class="text-sm text-gray-500 dark:text-gray-400">
        @if($paginator)
            @php
                $total = method_exists($paginator, 'total') ? $paginator->total() : ($paginator->total ?? 0);
            @endphp
            {{ __($totalLabel, ['total' => $total]) }}
        @endif
    </div>
    
    <!-- 表示件数選択（右側） -->
    <div class="flex items-center gap-2">
        <label for="perPage" class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __($perPageLabel) }}:
        </label>
        @include('components::form.select', [
            'id' => 'perPage',
            'name' => 'per_page',
            'options' => collect($perPageOptions)->mapWithKeys(function($count) {
                return [$count => $count . '件'];
            })->toArray(),
            'value' => $currentPerPage,
            'class' => 'w-auto min-w-[80px]'
        ])
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const perPageSelect = document.getElementById('perPage');
    
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('per_page', this.value);
            currentUrl.searchParams.delete('page'); // ページ番号をリセット
            
            window.location.href = currentUrl.toString();
        });
    }
});
</script>
@endpush
