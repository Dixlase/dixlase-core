{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

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

@extends('layouts.admin')

@section('content')
    <div class="container mx-auto p-6">
        <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
            @if(in_array($media->type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']))
                <img src="{{ asset('storage/' . config('admin.mediaPath') . '/' . $media->path) }}" alt="{{ $media->name }}" class="w-full h-auto object-cover rounded">
            @else
                <p class="text-gray-700">{{ __('admin/media.preview.no_preview') }}</p>
            @endif

            <p class="mt-4"><strong>{{ __('common.file_name') }}</strong> {{ $media->name }}</p>
            <p><strong>{{ __('common.file_type') }}</strong> {{ $media->type }}</p>
            <p><strong>{{ __('common.upload_date') }}</strong> {{ $media->created_at->format('Y-m-d H:i:s') }}</p>
            <p><strong>{{ __('common.uploaded_by') }}</strong> {{ $media->member->name ?? __('admin/media.preview.unknown') }}</p>
            
            <!-- メディア情報編集フォーム -->
            <form action="{{ route('admin.media.update', $media->id) }}" method="POST" class="mt-6 space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label for="caption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('common.caption') }}
                    </label>
                    <input type="text" 
                           id="caption" 
                           name="caption" 
                           value="{{ old('caption', $media->caption) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="{{ __('common.caption_placeholder') }}">
                    @error('caption')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="alt_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('common.alt_text') }}
                    </label>
                    <input type="text" 
                           id="alt_text" 
                           name="alt_text" 
                           value="{{ old('alt_text', $media->alt_text) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="{{ __('common.alt_text_placeholder') }}">
                    @error('alt_text')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('common.description') }}
                    </label>
                    <textarea id="description" 
                              name="description" 
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="{{ __('common.description_placeholder') }}">{{ old('description', $media->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>{{ __('common.save') }}
                    </button>
                </div>
            </form>
            
            <!-- メディアURL表示 -->
            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-gray-100">{{ __('admin/media.preview.media_url') }}</h3>
                <div class="flex items-center gap-2">
                    <input type="text" id="mediaUrl" value="{{ asset('storage/' . config('admin.mediaPath') . '/' . $media->path) }}" 
                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm" 
                           readonly>
                    <button onclick="copyToClipboard()" 
                            class="bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition dark:bg-green-600 dark:hover:bg-green-700 flex items-center gap-2">
                        <i class="fas fa-copy"></i> {{ __('common.copy') }}
                    </button>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ __('admin/media.preview.url_description') }}</p>
            </div>

            <div class="flex items-center gap-2 mt-4 justify-between">
                <a href="{{ route('admin.media.index') }}" class="bg-gray-500 text-white py-2 px-4 rounded flex items-center gap-2 hover:bg-gray-600 transition dark:bg-gray-600 dark:hover:bg-gray-700">
                    <i class="fas fa-arrow-left"></i> {{ __('common.back') }}
                </a>

                <div class="flex gap-2">
                    <a href="{{ route('admin.media.download', $media->id) }}" class="bg-blue-500 text-white py-2 px-4 rounded flex items-center gap-2 hover:bg-blue-600 transition dark:bg-blue-600 dark:hover:bg-blue-700">
                        <i class="fas fa-download"></i> {{ __('common.download') }}
                    </a>

                    <form action="{{ route('admin.media.delete', $media->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <!-- 削除ボタン -->
                        <button type="button" onclick="openModal('deleteModal')" class="bg-red-500 text-white py-2 px-4 rounded flex items-center gap-2 hover:bg-red-600 transition dark:bg-red-600 dark:hover:bg-red-700">
                            <i class="fas fa-trash-alt"></i> {{ __('common.delete') }}
                        </button>

                        <!-- 削除モーダル -->
                        <x-ui.modal
                            id="deleteModal"
                            :title="__('admin/media.preview.delete_confirmation')"
                            :message="__('admin/media.preview.delete_message')"
                            :confirm_label="__('common.delete')"
                            :cancel_label="__('common.cancel')"
                        />
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script @cspNonce>
function copyToClipboard() {
    const urlInput = document.getElementById('mediaUrl');
    const copyButton = event.target.closest('button');
    const originalText = copyButton.innerHTML;
    
    urlInput.select();
    urlInput.setSelectionRange(0, urlInput.value.length); // モバイル対応
    
    navigator.clipboard.writeText(urlInput.value).then(function() {
        // 成功時のフィードバック
        copyButton.innerHTML = '<i class="fas fa-check"></i> {{ __('common.copied') }}';
        copyButton.classList.remove('bg-green-500', 'hover:bg-green-600', 'dark:bg-green-600', 'dark:hover:bg-green-700');
        copyButton.classList.add('bg-blue-500', 'hover:bg-blue-600', 'dark:bg-blue-600', 'dark:hover:bg-blue-700');
        
        // 2秒後に元に戻す
        setTimeout(function() {
            copyButton.classList.remove('bg-blue-500', 'hover:bg-blue-600', 'dark:bg-blue-600', 'dark:hover:bg-blue-700');
            copyButton.classList.add('bg-green-500', 'hover:bg-green-600', 'dark:bg-green-600', 'dark:hover:bg-green-700');
        }, 2000);
    }).catch(function(err) {
        // エラー時のフォールバック（古いブラウザ対応）
        try {
            document.execCommand('copy');
            copyButton.innerHTML = '<i class="fas fa-check"></i> {{ __('common.copied') }}';
            setTimeout(function() {
                copyButton.innerHTML = originalText;
            }, 2000);
        } catch (e) {
            alert('{{ __('admin/media.preview.copy_failed') }}');
        }
    });
}
</script>
@endsection
