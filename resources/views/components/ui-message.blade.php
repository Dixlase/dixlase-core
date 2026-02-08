@props([
    'type' => 'info', // success, warning, error, notice, info
    'icon' => null, // カスタムアイコンクラス（指定しない場合は自動選択）
    'dismissible' => false, // 閉じるボタンを表示するか
    'message' => '', // メッセージ内容
    'textSize' => '', // フォントサイズ（text-sm, text-base, text-lg など）
])

@php
    // タイプに応じたクラスとアイコンの設定
    $typeConfig = [
        'success' => [
            'class' => 'message success bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
            'icon' => 'fas fa-check-circle text-green-500 dark:text-green-400'
        ],
        'warning' => [
            'class' => 'message warning bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
            'icon' => 'fas fa-exclamation-triangle text-yellow-500 dark:text-yellow-400'
        ],
        'error' => [
            'class' => 'message error bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
            'icon' => 'fas fa-times-circle text-red-500 dark:text-red-400'
        ],
        'notice' => [
            'class' => 'message notice bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200',
            'icon' => 'fas fa-info-circle text-blue-500 dark:text-blue-400'
        ],
        'info' => [
            'class' => 'message info bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200',
            'icon' => 'fas fa-info-circle text-blue-500 dark:text-blue-400'
        ]
    ];

    $config = $typeConfig[$type] ?? $typeConfig['info'];
    $iconClass = $icon ?? $config['icon'];
@endphp

<div class="my-4 p-4 rounded-lg {{ $config['class'] }}">
    <div class="flex items-start">
        @if($iconClass)
            <div class="flex-shrink-0">
                <i class="{{ $iconClass }} text-sm"></i>
            </div>
        @endif
        
        <div class="{{ $iconClass ? 'ml-2' : '' }} flex-1 {{ $textSize }}">
            {!! $message !!}
        </div>
        
        @if($dismissible)
            <div class="ml-auto pl-3">
                <button @click="$el.closest('.ui-message').remove()" 
                        class="inline-flex text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif
    </div>
</div>
