{{--
This file is part of MySoftware.

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

@extends('admin::partials.layout')

@section('content')
    <div class="container mx-auto p-6">
        <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
            @if(in_array($media->type, ['image/jpeg', 'image/png', 'image/gif']))
                <img src="{{ asset('storage/' . config('admin.mediaPath') . '/' . $media->path) }}" alt="{{ $media->name }}" class="w-full h-auto object-cover rounded">
            @else
                <p class="text-gray-700">ファイルはプレビューできません。</p>
            @endif

            <p class="mt-4"><strong>ファイル名:</strong> {{ $media->name }}</p>
            <p><strong>ファイルタイプ:</strong> {{ $media->type }}</p>
            <p><strong>アップロード日時:</strong> {{ $media->created_at->format('Y-m-d H:i:s') }}</p>
            <p><strong>アップロードしたメンバー:</strong> {{ $media->member->name ?? '不明' }}</p>
            
            <!-- メディアURL表示 -->
            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-gray-100">メディアURL</h3>
                <div class="flex items-center gap-2">
                    <input type="text" id="mediaUrl" value="{{ asset('storage/' . config('admin.mediaPath') . '/' . $media->path) }}" 
                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm" 
                           readonly>
                    <button onclick="copyToClipboard()" 
                            class="bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition dark:bg-green-600 dark:hover:bg-green-700 flex items-center gap-2">
                        <i class="fas fa-copy"></i> コピー
                    </button>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">このURLを使用してメディアファイルに直接アクセスできます。</p>
            </div>

            <div class="flex items-center gap-2 mt-4 justify-between">
                <a href="{{ route('admin.media.index') }}" class="bg-gray-500 text-white py-2 px-4 rounded flex items-center gap-2 hover:bg-gray-600 transition dark:bg-gray-600 dark:hover:bg-gray-700">
                    <i class="fas fa-arrow-left"></i> 戻る
                </a>

                <div class="flex gap-2">
                    <a href="{{ route('admin.media.download', $media->id) }}" class="bg-blue-500 text-white py-2 px-4 rounded flex items-center gap-2 hover:bg-blue-600 transition dark:bg-blue-600 dark:hover:bg-blue-700">
                        <i class="fas fa-download"></i> ダウンロード
                    </a>

                    <form action="{{ route('admin.media.delete', $media->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <!-- 削除ボタン -->
                        @include('components::form.button', [
                            'type' => 'button',
                            'label' => '削除',
                            'class' => 'text-white bg-red-700 hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900',
                            'onclick' => "openModal('deleteModal')",
                        ])

                        <!-- 削除モーダル -->
                        @include('components::form.modal', [
                            'id' => 'deleteModal',
                            'title' => '削除の確認',
                            'message' => 'このユーザーを削除しますか？',
                            'confirm_label' => '削除',
                            'cancel_label' => 'キャンセル',
                        ])
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
function copyToClipboard() {
    const urlInput = document.getElementById('mediaUrl');
    const copyButton = event.target.closest('button');
    const originalText = copyButton.innerHTML;
    
    // URLをクリップボードにコピー
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // モバイル対応
    
    navigator.clipboard.writeText(urlInput.value).then(function() {
        // 成功時のフィードバック
        copyButton.innerHTML = '<i class="fas fa-check"></i> コピー済み';
        copyButton.classList.remove('bg-green-500', 'hover:bg-green-600', 'dark:bg-green-600', 'dark:hover:bg-green-700');
        copyButton.classList.add('bg-blue-500', 'hover:bg-blue-600', 'dark:bg-blue-600', 'dark:hover:bg-blue-700');
        
        // 2秒後に元に戻す
        setTimeout(function() {
            copyButton.innerHTML = originalText;
            copyButton.classList.remove('bg-blue-500', 'hover:bg-blue-600', 'dark:bg-blue-600', 'dark:hover:bg-blue-700');
            copyButton.classList.add('bg-green-500', 'hover:bg-green-600', 'dark:bg-green-600', 'dark:hover:bg-green-700');
        }, 2000);
    }).catch(function(err) {
        // エラー時のフォールバック（古いブラウザ対応）
        try {
            document.execCommand('copy');
            copyButton.innerHTML = '<i class="fas fa-check"></i> コピー済み';
            setTimeout(function() {
                copyButton.innerHTML = originalText;
            }, 2000);
        } catch (e) {
            alert('コピーに失敗しました。手動でURLを選択してコピーしてください。');
        }
    });
}
</script>
@endsection
