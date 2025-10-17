@props([
    'id' => 'media_picker',
    'name' => 'media_id',
    'label' => null,
    'value' => null,
    'media' => null,
    'help' => null,
    'required' => false,
    'error' => null,
    'aspectRatio' => 'original', // 'original', 'ogp' (1.91:1), 'square' (1:1), '16:9', '4:3', 'hero' (21:9)
    'buttonText' => null, // ボタンのテキスト（指定しない場合はデフォルト）
])

@php
    $inputId = $name;
    $previewId = $name . '_preview';
    $selectorId = $name . '_selector';
    
    // アスペクト比に応じたクラスを設定
    $aspectClasses = match($aspectRatio) {
        'ogp' => 'aspect-[1.91/1] object-cover',
        'square' => 'aspect-square object-cover',
        '16:9' => 'aspect-video object-cover',
        '4:3' => 'aspect-[4/3] object-cover',
        'hero' => 'aspect-[21/9] object-cover',
        'original' => 'h-auto object-contain',
        default => 'h-auto object-contain',
    };
@endphp

<div class="mb-4">
    @if($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-600">*</span>
            @endif
        </label>
    @endif
    
    <!-- 隠しフィールド -->
    <input type="hidden" id="{{ $inputId }}" name="{{ $name }}" value="{{ old($name, $value) }}">
    
    <!-- プレビュー表示 -->
    <div id="{{ $previewId }}" class="mb-4">
        @if($media)
            <div class="relative inline-block">
                <img src="{{ asset('storage/media/' . $media->path) }}" 
                     alt="{{ $media->name }}" 
                     class="w-64 {{ $aspectClasses }} rounded border border-gray-300 dark:border-gray-600">
                <button type="button" 
                        onclick="removeMediaPreview('{{ $inputId }}', '{{ $previewId }}')" 
                        class="absolute -top-2 -right-2 w-8 h-8 bg-red-600 text-white rounded-full hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                        title="{{ __('common.delete') }}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif
    </div>
    
    <!-- 選択ボタン -->
    <button type="button" 
            onclick="openMediaSelector('{{ $selectorId }}', '{{ $inputId }}', '{{ $previewId }}', false, '{{ $aspectRatio }}')"
            class="px-4 py-2 mb-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <i class="fas fa-image mr-2"></i>{{ $buttonText ?? __('admin.settings.base.select_ogp_image') }}
    </button>
    
    @if($help)
        <p class="text-sm">{{ $help }}</p>
    @endif
    
    @if($error)
        <p class="mt-4 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>

<!-- メディア選択モーダル -->
@push('modals')
    @include('components.media-selector', [
        'id' => $selectorId,
        'inputId' => $inputId,
        'previewId' => $previewId,
        'multiple' => false
    ])
@endpush
