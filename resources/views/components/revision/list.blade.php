{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-revision.list />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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

{{--
共通リビジョン一覧コンポーネント。

プラグイン/テーマから以下のプロパティを渡して再利用できる:

- $revisions: LengthAwarePaginator<\Illuminate\Database\Eloquent\Model> — creator リレーションを eager load 済みのリビジョン
- $typeLabels: array<string, string> — type の翻訳ラベル（auto / manual / restore_backup）
- $retention: int — 現在の保持件数設定（0 のときサマリバッジ非表示）
- $protectedCount: int — 保護中リビジョン件数
- $backRoute: string — 編集画面などへの戻り先 URL
- $backLabel: string — 戻るリンクのテキスト
- $showRouteName: string — 差分詳細画面のルート名（例: admin.front.revisions.show）
- $restoreRouteName: string — 復元アクションのルート名
- $protectRouteName: string — 保護トグルのルート名
- $parentParams: array — 各ルートの先頭に付与する親パラメータ（複数階層対応）
- $translationPrefix: string — 翻訳キープレフィックス（例: admin/front/revisions）
--}}

@props([
    'revisions',
    'typeLabels' => [],
    'retention' => 0,
    'protectedCount' => 0,
    'backRoute' => null,
    'backLabel' => null,
    'showRouteName',
    'restoreRouteName',
    'protectRouteName',
    'parentParams' => [],
    'translationPrefix' => 'admin/front/revisions',
])

<div class="mx-auto">
    <div class="mb-4 flex items-center justify-between gap-4">
        @if ($backRoute)
            <a href="{{ $backRoute }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                <i class="fas fa-arrow-left"></i>
                {{ $backLabel ?? __($translationPrefix.'.back_to_edit') }}
            </a>
        @else
            <div></div>
        @endif

        @if ($retention > 0)
            <span class="inline-flex items-center gap-2 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1 text-xs text-gray-700 dark:text-gray-300"
                  title="{{ __($translationPrefix.'.protect_help') }}">
                <i class="fas fa-shield-halved text-blue-500 dark:text-blue-400"></i>
                @if ($protectedCount > $retention)
                    {{ __($translationPrefix.'.protect_count_summary_over', ['protected' => $protectedCount, 'retention' => $retention]) }}
                @else
                    {{ __($translationPrefix.'.protect_count_summary', ['protected' => $protectedCount, 'retention' => $retention]) }}
                @endif
            </span>
        @endif
    </div>

    @if ($revisions->isEmpty())
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __($translationPrefix.'.no_revisions') }}</p>
        </div>
    @else
        <section>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.created_at') }}</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.type') }}</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.creator') }}</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.note') }}</th>
                        <th scope="col" class="px-4 py-2 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.protect') }}</th>
                        <th scope="col" class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($revisions as $revision)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $revision->created_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ $typeLabels[$revision->type] ?? $revision->type }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                {{ $revision->creator->display_name ?? $revision->creator->account_name ?? __($translationPrefix.'.unknown_user') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $revision->note }}
                            </td>
                            <td class="px-4 py-2 text-sm text-center whitespace-nowrap">
                                <form action="{{ route($protectRouteName, [...$parentParams, $revision->id]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="{{ $revision->is_protected ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-blue-600 dark:text-gray-500 dark:hover:text-blue-400' }}"
                                            title="{{ $revision->is_protected ? __($translationPrefix.'.protect_disable') : __($translationPrefix.'.protect_enable') }}">
                                        <i class="fas {{ $revision->is_protected ? 'fa-shield-halved' : 'fa-shield' }}"></i>
                                        <span class="sr-only">{{ $revision->is_protected ? __($translationPrefix.'.protect_label_on') : __($translationPrefix.'.protect_label_off') }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-2 text-sm text-right whitespace-nowrap">
                                <a href="{{ route($showRouteName, [...$parentParams, $revision->id]) }}"
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                   title="{{ __($translationPrefix.'.view_diff') }}">
                                    <i class="fas fa-code-compare"></i>
                                    <span class="sr-only">{{ __($translationPrefix.'.view_diff') }}</span>
                                </a>
                                <form id="restore-form-{{ $revision->id }}" action="{{ route($restoreRouteName, [...$parentParams, $revision->id]) }}" method="POST" class="inline ml-3">
                                    @csrf
                                    <button type="button" @click="openModal('restore-modal-{{ $revision->id }}')"
                                            class="text-amber-600 hover:text-amber-800 dark:text-amber-400"
                                            title="{{ __($translationPrefix.'.restore') }}">
                                        <i class="fas fa-rotate-left"></i>
                                        <span class="sr-only">{{ __($translationPrefix.'.restore') }}</span>
                                    </button>
                                </form>
                                <x-ui-modal
                                    :id="'restore-modal-'.$revision->id"
                                    :title="__($translationPrefix.'.restore_confirm_title')"
                                    :message="__($translationPrefix.'.restore_confirm_message')"
                                    :confirm_label="__($translationPrefix.'.restore')"
                                    :cancel_label="__('common.cancel')"
                                    icon_type="warning"
                                    confirm_color="yellow"
                                    :form="'restore-form-'.$revision->id"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $revisions->links() }}
            </div>
        </section>
    @endif
</div>
