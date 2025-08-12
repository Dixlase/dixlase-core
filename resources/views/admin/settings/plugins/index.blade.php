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
    <!-- プラグイン一覧テーブル -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">{{ __('admin.settings.plugins.index.table.id') }}</th>
                    <th class="py-3 px-6 text-left">{{ __('admin.settings.plugins.index.table.name') }}</th>
                    <th class="py-3 px-6 text-center">{{ __('admin.settings.plugins.index.table.status') }}</th>
                    <th class="py-3 px-6 text-center">{{ __('admin.settings.plugins.index.table.actions') }}</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm font-medium">
                @foreach ($plugins as $plugin)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-3 px-6">{{ $plugin->id }}</td>
                        <td class="py-3 px-6">{{ $plugin->name }}</td>
                        <td class="py-3 px-6 text-center">
                            <span
                                class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                                {{ $plugin->status === 'enabled' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}"
                            >
                                {{ $plugin->status === 1 ? __('admin.settings.plugins.index.status.enabled') : __('admin.settings.plugins.index.status.disabled') }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center flex justify-center space-x-2">
                            @if ($plugin->status === 1)
                                <form action="{{ route('admin.settings.plugins.disable', $plugin->id) }}" method="POST">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded-md text-sm"
                                    >
                                        {{ __('admin.settings.plugins.index.buttons.disable') }}
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.settings.plugins.enable', $plugin->id) }}" method="POST">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded-md text-sm"
                                    >
                                        {{ __('admin.settings.plugins.index.buttons.enable') }}
                                    </button>
                                </form>
                            @endif

                            <!-- 1) フォーム: uninstall.blade.php など（プラグイン一覧で繰り返し） -->
                            <form action="{{ route('admin.settings.plugins.uninstall', $plugin->id) }}" method="POST">
                                @csrf
                                @include('components::form.button', [
                                    'type' => 'button',
                                    'label' => __('admin.settings.plugins.index.buttons.uninstall'),
                                    'class' => 'bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-md text-sm',
                                    'onclick' => "openModal('uninstallModal-{$plugin->id}')",
                                ])

                                <!-- 確認画面のモーダルを表示 -->
                                @include('components.form.modal', [
                                    'id' => "uninstallModal-{$plugin->id}",
                                    'title' => __('admin.settings.plugins.index.uninstall.confirm_title'),
                                    'message' => str_replace('{name}', $plugin->name, __('admin.settings.plugins.index.uninstall.confirm_message')),
                                    'confirm_label' => __('admin.settings.plugins.index.uninstall.confirm_button'),
                                    'cancel_label'  => __('admin.settings.plugins.index.uninstall.cancel_button'),
                                    // チェックボックスを使いたい場合
                                    'checkbox' => true,
                                    'checkbox_name' => 'remove_db_data',
                                    'checkbox_label' => __('admin.settings.plugins.index.uninstall.remove_data_checkbox'),
                                ])
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
