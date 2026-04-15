{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-revision.diff />

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
共通リビジョン差分表示コンポーネント。

プロパティ:
- $revision: リビジョンモデル（creator リレーションを eager load 済み）
- $typeLabels: array<string, string> — type の翻訳ラベル
- $diffs: array<string, list<array{status: string, left: ?string, right: ?string}>>
    RevisionDiffPresenter::buildSideBySide() の出力をフィールド名キーで
- $metaDiffs: array<string, array{old: mixed, new: mixed}> — メタフィールド差分
- $hasChanges: bool
- $backRoute: string — 一覧ページへの戻り URL
- $restoreRouteName: string — 復元アクションのルート名
- $noteRouteName: string — メモ更新のルート名
- $protectRouteName: string — 保護トグルのルート名
- $parentParams: array — ルートに prepend する親パラメータ
- $translationPrefix: string — 翻訳キープレフィックス
--}}

@props([
    'revision',
    'typeLabels' => [],
    'diffs' => [],
    'metaDiffs' => [],
    'hasChanges' => false,
    'backRoute' => null,
    'restoreRouteName',
    'noteRouteName',
    'protectRouteName',
    'parentParams' => [],
    'translationPrefix' => 'admin/front/revisions',
])

@once
@push('styles')
<style>
    .diff-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.8125rem; line-height: 1.4; }
    .diff-cell { padding: 0.125rem 0.5rem; white-space: pre-wrap; word-break: break-word; border-top: 1px solid var(--color-gray-100, #f3f4f6); }
    .dark .diff-cell { border-top-color: var(--color-gray-800, #1f2937); }
    .diff-row.diff-same .diff-cell { background-color: transparent; }
    .diff-row.diff-removed .diff-cell-left { background-color: rgba(239, 68, 68, 0.15); }
    .diff-row.diff-added .diff-cell-right { background-color: rgba(34, 197, 94, 0.15); }
    .diff-row.diff-changed .diff-cell-left { background-color: rgba(239, 68, 68, 0.15); }
    .diff-row.diff-changed .diff-cell-right { background-color: rgba(34, 197, 94, 0.15); }
    .diff-header { display: grid; grid-template-columns: 1fr 1fr; font-weight: 600; font-size: 0.75rem; padding: 0.25rem 0.5rem; background: var(--color-gray-100, #f3f4f6); color: var(--color-gray-700, #374151); }
    .dark .diff-header { background: var(--color-gray-800, #1f2937); color: var(--color-gray-200, #e5e7eb); }
    .diff-header > div:first-child { border-right: 1px solid var(--color-gray-200, #e5e7eb); padding-right: 0.5rem; }
    .dark .diff-header > div:first-child { border-right-color: var(--color-gray-700, #374151); }
    .diff-cell-left { border-right: 1px solid var(--color-gray-200, #e5e7eb); }
    .dark .diff-cell-left { border-right-color: var(--color-gray-700, #374151); }
</style>
@endpush
@endonce

<div class="mx-auto">
    <div class="mb-4 flex items-center justify-between">
        @if ($backRoute)
            <a href="{{ $backRoute }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                <i class="fas fa-arrow-left"></i>
                {{ __($translationPrefix.'.heading') }}
            </a>
        @else
            <div></div>
        @endif

        <div class="flex items-center gap-2">
            <form action="{{ route($protectRouteName, [...$parentParams, $revision->id]) }}" method="POST">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded border px-3 py-1.5 text-sm font-medium {{ $revision->is_protected
                            ? 'border-blue-500 bg-blue-50 text-blue-700 hover:bg-blue-100 dark:border-blue-400 dark:bg-blue-900 dark:text-blue-100 dark:hover:bg-blue-800'
                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                    <i class="fas {{ $revision->is_protected ? 'fa-shield-halved' : 'fa-shield' }}"></i>
                    {{ $revision->is_protected ? __($translationPrefix.'.protect_disable') : __($translationPrefix.'.protect_enable') }}
                </button>
            </form>
            <form id="restore-form-{{ $revision->id }}" action="{{ route($restoreRouteName, [...$parentParams, $revision->id]) }}" method="POST">
                @csrf
                <button type="button" @click="openModal('restore-modal-{{ $revision->id }}')"
                        class="inline-flex items-center gap-2 rounded bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700">
                    <i class="fas fa-rotate-left"></i>
                    {{ __($translationPrefix.'.restore') }}
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
        </div>
    </div>

    <section class="mb-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-4">
            <dt class="text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.created_at') }}</dt>
            <dd class="text-gray-900 dark:text-gray-100 sm:col-span-3">{{ $revision->created_at?->format('Y-m-d H:i:s') }}</dd>
            <dt class="text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.type') }}</dt>
            <dd class="text-gray-900 dark:text-gray-100 sm:col-span-3">{{ $typeLabels[$revision->type] ?? $revision->type }}</dd>
            <dt class="text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.creator') }}</dt>
            <dd class="text-gray-900 dark:text-gray-100 sm:col-span-3">{{ $revision->creator->display_name ?? $revision->creator->account_name ?? __($translationPrefix.'.unknown_user') }}</dd>
        </dl>
    </section>

    <section class="mb-6">
        <form id="note-form-{{ $revision->id }}" action="{{ route($noteRouteName, [...$parentParams, $revision->id]) }}" method="POST" class="flex flex-col gap-2 sm:flex-row sm:items-start">
            @csrf
            <label for="revision-note-{{ $revision->id }}" class="shrink-0 pt-2 text-sm font-medium text-gray-700 dark:text-gray-200 sm:w-24">
                {{ __($translationPrefix.'.note') }}
            </label>
            <div class="flex-1">
                <x-form-textarea
                    id="revision-note-{{ $revision->id }}"
                    name="note"
                    :value="old('note', $revision->note)"
                    :rows="2"
                    :placeholder="__($translationPrefix.'.note_placeholder')"
                    class="input-full"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __($translationPrefix.'.note_help') }}</p>
            </div>
            <button type="button" @click="openModal('note-modal-{{ $revision->id }}')"
                    class="shrink-0 rounded bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                {{ __($translationPrefix.'.note_save') }}
            </button>
        </form>
        <x-ui-modal
            :id="'note-modal-'.$revision->id"
            :title="__($translationPrefix.'.note_confirm_title')"
            :message="__($translationPrefix.'.note_confirm_message')"
            :confirm_label="__($translationPrefix.'.note_save')"
            :cancel_label="__('common.cancel')"
            icon_type="info"
            confirm_color="blue"
            :form="'note-form-'.$revision->id"
        />
    </section>

    <section>
        <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __($translationPrefix.'.diff_heading') }}</h2>

        @if (! $hasChanges)
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __($translationPrefix.'.diff_no_changes') }}</p>
        @else
            @if (! empty($metaDiffs))
                <div class="mb-6">
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __($translationPrefix.'.diff_meta_heading') }}</h3>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-1 text-left font-medium"></th>
                                <th class="px-3 py-1 text-left font-medium">{{ __($translationPrefix.'.diff_left_label') }}</th>
                                <th class="px-3 py-1 text-left font-medium">{{ __($translationPrefix.'.diff_right_label') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($metaDiffs as $field => $values)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-3 py-1 font-medium text-gray-700 dark:text-gray-200">{{ __($translationPrefix.'.diff_field_'.$field) }}</td>
                                    <td class="px-3 py-1 text-gray-900 dark:text-gray-100">{{ is_scalar($values['old']) ? $values['old'] : json_encode($values['old']) }}</td>
                                    <td class="px-3 py-1 text-gray-900 dark:text-gray-100">{{ is_scalar($values['new']) ? $values['new'] : json_encode($values['new']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @foreach ($diffs as $field => $rows)
                <div class="mb-6">
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __($translationPrefix.'.diff_field_'.$field) }}</h3>
                    <div class="rounded border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="diff-header">
                            <div>{{ __($translationPrefix.'.diff_left_label') }}</div>
                            <div>{{ __($translationPrefix.'.diff_right_label') }}</div>
                        </div>
                        @foreach ($rows as $row)
                            <div class="diff-row diff-{{ $row['status'] }}">
                                <div class="diff-cell diff-cell-left">{{ $row['left'] ?? '' }}</div>
                                <div class="diff-cell diff-cell-right">{{ $row['right'] ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </section>
</div>
