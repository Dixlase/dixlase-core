{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-content.file-changes />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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
"The files have changes that are not published" notice

Shown on the edit screen of file-stored content under the admin publish mode
(#555): what changed in each part, published copy against file, and a Publish
button that posts to the owner's publish route.

Props:
- $changes: array<string, list<array{status: string, left: ?string, right: ?string}>>
  part => rows from RevisionDiffPresenter::buildSideBySide(published, file)
- $publishUrl: the route that runs PublishFileContentAction for this record
- $canPublish: whether the viewer may publish (edit permission)
- $partLabels: optional part => label; defaults to common.file_changes.part_{part}
- $id: id prefix for the form and the confirmation modal
--}}

@props([
    'changes' => [],
    'publishUrl' => '',
    'canPublish' => true,
    'partLabels' => [],
    'id' => 'file-changes',
])

@if (! empty($changes))
    @php
        $formId = $id.'-form';
        $modalId = $id.'-confirm';
        $rowClasses = [
            'same' => ['', ''],
            'removed' => ['bg-red-500/15', ''],
            'added' => ['', 'bg-green-500/15'],
            'changed' => ['bg-red-500/15', 'bg-green-500/15'],
        ];
    @endphp

    <section {{ $attributes->merge(['class' => 'mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/40']) }}
        role="region" aria-labelledby="{{ $id }}-title">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <i class="fas fa-file-pen mt-1 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                <div>
                    <h2 id="{{ $id }}-title" class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                        {{ __('common.file_changes.title') }}
                    </h2>
                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                        {{ __('common.file_changes.description') }}
                    </p>
                    @unless ($canPublish)
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ __('common.file_changes.view_only') }}</p>
                    @endunless
                </div>
            </div>

            @if ($canPublish)
                <form id="{{ $formId }}" action="{{ $publishUrl }}" method="POST">
                    @csrf
                </form>
                <x-form-button
                    type="button"
                    variant="warning"
                    size="sm"
                    :label="__('common.file_changes.publish')"
                    icon="fas fa-upload"
                    @click="openModal('{{ $modalId }}')"
                />
            @endif
        </div>

        @foreach ($changes as $part => $rows)
            <div class="mt-4">
                <h3 class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                    {{ $partLabels[$part] ?? __('common.file_changes.part_'.$part) }}
                </h3>
                <div class="max-h-80 overflow-auto rounded border border-gray-200 bg-white font-mono text-xs dark:border-gray-700 dark:bg-gray-900">
                    <div class="sticky top-0 grid grid-cols-2 bg-gray-100 px-2 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <div>{{ __('common.file_changes.published') }}</div>
                        <div>{{ __('common.file_changes.file') }}</div>
                    </div>
                    @foreach ($rows as $row)
                        @php [$leftClass, $rightClass] = $rowClasses[$row['status']] ?? ['', '']; @endphp
                        <div class="grid grid-cols-2 border-t border-gray-100 dark:border-gray-800">
                            <div class="whitespace-pre-wrap break-words border-r border-gray-200 px-2 py-0.5 dark:border-gray-700 {{ $leftClass }}">{{ $row['left'] ?? '' }}</div>
                            <div class="whitespace-pre-wrap break-words px-2 py-0.5 {{ $rightClass }}">{{ $row['right'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>

    @if ($canPublish)
        @push('modals')
            <x-ui-modal
                :id="$modalId"
                :title="__('common.file_changes.confirm_title')"
                :message="__('common.file_changes.confirm_message')"
                :confirm_label="__('common.file_changes.publish')"
                :cancel_label="__('common.cancel')"
                :form="$formId"
            />
        @endpush
    @endif
@endif
