@props([
    'type' => 'info', // success, warning, error, notice, info
    'icon' => null, // カスタムアイコンクラス（指定しない場合は自動選択）
    'dismissible' => false, // 閉じるボタンを表示するか
    'message' => '', // メッセージ内容
])

@php
    // タイプに応じたクラスとアイコンの設定
    $typeConfig = [
        'success' => [
            'class' => 'message success',
            'icon' => 'fas fa-check-circle text-green-400'
        ],
        'warning' => [
            'class' => 'message warning',
            'icon' => 'fas fa-exclamation-triangle text-yellow-400'
        ],
        'error' => [
            'class' => 'message error',
            'icon' => 'fas fa-times-circle text-red-400'
        ],
        'notice' => [
            'class' => 'message notice',
            'icon' => 'fas fa-info-circle text-blue-400'
        ],
        'info' => [
            'class' => 'message info',
            'icon' => 'fas fa-info-circle text-blue-400'
        ]
    ];

    $config = $typeConfig[$type] ?? $typeConfig['info'];
    $iconClass = $icon ?? $config['icon'];
@endphp

<div class="my-6 {{ $config['class'] }}">
    <div class="flex items-start">
        @if($iconClass)
            <div class="flex-shrink-0">
                <i class="{{ $iconClass }} text-sm"></i>
            </div>
        @endif
        
        <div class="{{ $iconClass ? 'ml-2' : '' }} flex-1">
            {!! $message !!}
        </div>
        
        @if($dismissible)
            <div class="ml-auto pl-3">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                        class="inline-flex text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif
    </div>
</div>
