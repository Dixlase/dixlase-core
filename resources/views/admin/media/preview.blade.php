{{--
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
Website: https://exc-d.com

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
            <p><strong>アップロードしたメンバー:</strong> {{ $media->user->name ?? '不明' }}</p>

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

