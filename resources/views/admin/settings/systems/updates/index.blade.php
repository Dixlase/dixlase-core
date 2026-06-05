{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.
--}}

@extends('layouts.admin')

@section('content')
{{--
    Per-row single-item update wiring.

    `pendingSingleUpdate` carries the in-flight target across the row
    button → confirm modal → mini-form submit handoff. `askSingleUpdate()`
    is invoked by each row's "更新" button with the target's kind / id /
    display name; it populates `pendingSingleUpdate` and opens the
    single-update confirm modal. The modal's confirm button then submits
    the matching hidden mini-form (one per updatable plugin / theme,
    rendered just below the core form) via the shared `submitModalForm()`
    helper. The bulk-apply checkboxes and their separate confirm modal
    remain unchanged — the two paths reuse the same `apply` controller
    endpoint, the only difference is the size of the `plugins[]` /
    `themes[]` array it receives.
--}}
<div class="mx-auto max-w-5xl"
     x-data="{
         pendingSingleUpdate: { kind: null, id: null, name: null, formId: null },
         askSingleUpdate(kind, id, name) {
             this.pendingSingleUpdate = {
                 kind: kind,
                 id: id,
                 name: name,
                 formId: `singleUpdateForm_${kind}_${id}`,
             };
             openModal('confirmSingleUpdateModal');
         },
     }">
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">{{ __('admin/settings/systems/updates.description') }}</p>

    {{-- Header: Last check time and recheck button --}}
    <div class="flex items-center justify-between gap-3 mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            @if($lastCheckedAtFormatted)
                {{ __('admin/settings/systems/updates.last_checked_at', ['date' => $lastCheckedAtFormatted]) }}
            @else
                {{ __('admin/settings/systems/updates.never_checked') }}
            @endif
        </div>
        <form method="POST" action="{{ route('admin.settings.systems.updates.check') }}">
            @csrf
            <x-form-button
                type="submit"
                :label="__('admin/settings/systems/updates.check_now')"
                variant="secondary"
                size="sm"
                icon="fas fa-sync-alt"
            />
        </form>
    </div>

    {{-- Standalone core update form. Lives outside the bulk-apply form
         below because nesting <form> elements is invalid HTML — the
         browser silently auto-closes the outer form at the inner <form>
         tag, which would break the bulk form's x-data scope and the
         selectedCount disable binding on its submit button. The Core
         section's button references this form by HTML5 form="..." id. --}}
    <form id="coreUpdateForm"
          method="POST"
          action="{{ route('admin.settings.systems.updates.apply-core') }}"
          class="hidden">
        @csrf
    </form>

    {{-- Hidden mini-forms backing the per-row "更新" buttons. One per
         updatable plugin and one per updatable theme. Each carries a
         single-element `plugins[]` / `themes[]` array that the existing
         `apply` controller handles transparently — the loop over
         selected IDs just runs once. Sit outside the bulk-apply form
         for the same nested-<form> reason as #coreUpdateForm above. --}}
    @foreach($plugins as $plugin)
        <form id="singleUpdateForm_plugin_{{ $plugin['id'] }}"
              method="POST"
              action="{{ route('admin.settings.systems.updates.apply') }}"
              class="hidden">
            @csrf
            <input type="hidden" name="plugins[]" value="{{ $plugin['id'] }}">
        </form>
    @endforeach
    @foreach($themes as $theme)
        <form id="singleUpdateForm_theme_{{ $theme['id'] }}"
              method="POST"
              action="{{ route('admin.settings.systems.updates.apply') }}"
              class="hidden">
            @csrf
            <input type="hidden" name="themes[]" value="{{ $theme['id'] }}">
        </form>
    @endforeach

    {{-- Main form: Selection + bulk apply --}}
    <form method="POST"
          action="{{ route('admin.settings.systems.updates.apply') }}"
          id="systemUpdatesApplyForm"
          x-data="{
              selectedCount: 0,
              applying: false,
              recompute() {
                  this.selectedCount = this.$root.querySelectorAll('input[type=checkbox][data-update-target]:checked').length;
              },
          }"
          x-init="recompute()"
          @change="recompute()"
          @submit="applying = true; closeModal('confirmSystemUpdatesModal'); $nextTick(() => openModal('updatesInProgressModal'))">
        @csrf

        {{-- Core section --}}
        <section class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                <i class="fas fa-cube mr-2"></i>{{ __('admin/settings/systems/updates.core.heading') }}
                @if($core['available'])
                    <span class="inline-flex items-center px-2 py-0.5 ml-2 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ __('admin/settings/systems/updates.core.update_available') }}
                    </span>
                @endif
            </h2>

            @if($core['available'])
                <div class="text-sm space-y-3">
                    <div class="flex items-baseline gap-3">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">{{ __('admin/settings/systems/updates.core.label') }}</span>
                        <span class="font-mono text-xs text-gray-600 dark:text-gray-400">v{{ $core['current_version'] }}</span>
                        <i class="fas fa-arrow-right text-[10px] text-gray-400"></i>
                        <span class="font-mono text-xs text-blue-700 dark:text-blue-300 font-semibold">v{{ $core['available_version'] }}</span>
                        @if($core['release_url'])
                            <a href="{{ $core['release_url'] }}" target="_blank" rel="noopener noreferrer" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                <i class="fas fa-external-link-alt text-[10px] mr-1"></i>{{ __('admin/settings/systems/updates.core.release_notes_link') }}
                            </a>
                        @endif
                    </div>

                    @if(! empty($core['update_failure_reason']))
                        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3">
                            <p class="font-semibold text-sm text-red-800 dark:text-red-200 mb-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('admin/settings/systems/updates.core.update_failed_heading') }}
                                @if(! empty($core['update_failed_at_formatted']))
                                    <span class="text-xs font-normal text-red-700 dark:text-red-300 ml-1">({{ $core['update_failed_at_formatted'] }})</span>
                                @endif
                            </p>
                            <p class="font-mono text-xs text-red-700 dark:text-red-300 break-words">{{ $core['update_failure_reason'] }}</p>
                        </div>
                    @endif

                    {{-- Submits the standalone #coreUpdateForm declared
                         above this view's bulk-apply form, via HTML5's
                         form="..." attribute. This keeps the button
                         visually here without nesting <form> tags. --}}
                    <div class="flex">
                        <x-form-button type="submit"
                            form="coreUpdateForm"
                            :label="__('admin/settings/systems/updates.core.update_button')"
                            variant="primary"
                            icon="fas fa-cloud-download-alt" />
                    </div>

                    <details class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3">
                        <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-100 flex items-center gap-2 hover:text-gray-900 dark:hover:text-white transition-colors">
                            <i class="fas fa-terminal text-gray-500 dark:text-gray-400"></i>{{ __('admin/settings/systems/updates.core.cli_alternative_heading') }}
                        </summary>
                        <div class="mt-3 space-y-2">
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('admin/settings/systems/updates.core.cli_alternative_intro') }}</p>
                            <div class="flex items-center gap-2"
                                 x-data="{ copied: false, copy() { navigator.clipboard.writeText(this.$refs.cmd.textContent.trim()).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }">
                                <code x-ref="cmd" class="flex-1 font-mono text-xs bg-gray-900 text-gray-100 px-3 py-2 rounded select-all">{{ __('admin/settings/systems/updates.core.cli_command') }}</code>
                                <button type="button" @click="copy()"
                                        class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    <i class="fas" :class="copied ? 'fa-check text-green-500' : 'fa-copy'"></i>
                                    <span x-text="copied ? '{{ __('common.copied') }}' : '{{ __('common.copy') }}'"></span>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/updates.core.cli_followups') }}</p>
                        </div>
                    </details>
                </div>
            @else
                @if(! empty($core['current_version']))
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        {{ __('admin/settings/systems/updates.core.current_version', ['version' => $core['current_version']]) }}
                    </p>
                @endif
                <p class="text-xs text-gray-500 dark:text-gray-500 italic">
                    <i class="fas fa-check-circle mr-1 text-green-500"></i>{{ __('admin/settings/systems/updates.core.up_to_date') }}
                </p>
            @endif
        </section>

        {{-- plugin section --}}
        <section class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                <i class="fas fa-plug mr-2"></i>{{ __('admin/settings/systems/updates.plugins.heading') }}
                @if(count($plugins) > 0)
                    <span class="inline-flex items-center px-2 py-0.5 ml-2 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ __('admin/settings/systems/updates.plugins.count', ['count' => count($plugins)]) }}
                    </span>
                @endif
            </h2>

            @if(count($plugins) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('admin/settings/systems/updates.plugins.none') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-2 w-8">
                                <input type="checkbox"
                                       class="rounded border-gray-300 dark:border-gray-600"
                                       @change="$root.querySelectorAll('input[type=checkbox][data-update-target=plugin]').forEach(cb => cb.checked = $event.target.checked); recompute()"
                                       aria-label="{{ __('admin/settings/systems/updates.select_all') }}">
                            </th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.name') }}</th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.current') }}</th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.available') }}</th>
                            <th class="py-2 pl-2 text-right w-32"><span class="sr-only">{{ __('admin/settings/systems/updates.table.action') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plugins as $plugin)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 pr-2">
                                    <input type="checkbox"
                                           name="plugins[]"
                                           value="{{ $plugin['id'] }}"
                                           data-update-target="plugin"
                                           @checked($plugin['preselected'])
                                           class="rounded border-gray-300 dark:border-gray-600"
                                           id="plugin-{{ $plugin['id'] }}">
                                </td>
                                <td class="py-2 pr-4">
                                    <label for="plugin-{{ $plugin['id'] }}" class="cursor-pointer text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $plugin['name'] }}
                                    </label>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $plugin['slug'] }}</span>
                                    @if($plugin['updateFailedAt'])
                                        <span class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800"
                                              title="{{ $plugin['updateFailureReason'] }}">
                                            <i class="fas fa-exclamation-triangle text-[10px]"></i>
                                            {{ __('admin/settings/systems/updates.failure.previous_failure', ['date' => $plugin['updateFailedAtFormatted']]) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-600 dark:text-gray-400">v{{ $plugin['currentVersion'] }}</td>
                                <td class="py-2 pr-4 font-mono text-xs text-blue-700 dark:text-blue-300 font-semibold">
                                    <i class="fas fa-arrow-up text-[10px] mr-1"></i>v{{ $plugin['availableVersion'] }}
                                </td>
                                <td class="py-2 pl-2 text-right">
                                    <x-form-button
                                        type="button"
                                        size="sm"
                                        variant="primary"
                                        icon="fas fa-cloud-arrow-down"
                                        :label="__('admin/settings/systems/updates.apply_one')"
                                        data-update-id="{{ $plugin['id'] }}"
                                        data-update-name="{{ $plugin['name'] }}"
                                        data-update-kind="plugin"
                                        xClick="askSingleUpdate($el.dataset.updateKind, parseInt($el.dataset.updateId, 10), $el.dataset.updateName)"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        {{-- theme section --}}
        <section class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                <i class="fas fa-palette mr-2"></i>{{ __('admin/settings/systems/updates.themes.heading') }}
                @if(count($themes) > 0)
                    <span class="inline-flex items-center px-2 py-0.5 ml-2 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ __('admin/settings/systems/updates.themes.count', ['count' => count($themes)]) }}
                    </span>
                @endif
            </h2>

            @if(count($themes) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('admin/settings/systems/updates.themes.none') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-2 w-8">
                                <input type="checkbox"
                                       class="rounded border-gray-300 dark:border-gray-600"
                                       @change="$root.querySelectorAll('input[type=checkbox][data-update-target=theme]').forEach(cb => cb.checked = $event.target.checked); recompute()"
                                       aria-label="{{ __('admin/settings/systems/updates.select_all') }}">
                            </th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.name') }}</th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.current') }}</th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.available') }}</th>
                            <th class="py-2 pl-2 text-right w-32"><span class="sr-only">{{ __('admin/settings/systems/updates.table.action') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($themes as $theme)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 pr-2">
                                    <input type="checkbox"
                                           name="themes[]"
                                           value="{{ $theme['id'] }}"
                                           data-update-target="theme"
                                           @checked($theme['preselected'])
                                           class="rounded border-gray-300 dark:border-gray-600"
                                           id="theme-{{ $theme['id'] }}">
                                </td>
                                <td class="py-2 pr-4">
                                    <label for="theme-{{ $theme['id'] }}" class="cursor-pointer text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $theme['name'] }}
                                    </label>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $theme['slug'] }}</span>
                                    @if($theme['updateFailedAt'])
                                        <span class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800"
                                              title="{{ $theme['updateFailureReason'] }}">
                                            <i class="fas fa-exclamation-triangle text-[10px]"></i>
                                            {{ __('admin/settings/systems/updates.failure.previous_failure', ['date' => $theme['updateFailedAtFormatted']]) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-600 dark:text-gray-400">v{{ $theme['currentVersion'] }}</td>
                                <td class="py-2 pr-4 font-mono text-xs text-blue-700 dark:text-blue-300 font-semibold">
                                    <i class="fas fa-arrow-up text-[10px] mr-1"></i>v{{ $theme['availableVersion'] }}
                                </td>
                                <td class="py-2 pl-2 text-right">
                                    <x-form-button
                                        type="button"
                                        size="sm"
                                        variant="primary"
                                        icon="fas fa-cloud-arrow-down"
                                        :label="__('admin/settings/systems/updates.apply_one')"
                                        data-update-id="{{ $theme['id'] }}"
                                        data-update-name="{{ $theme['name'] }}"
                                        data-update-kind="theme"
                                        xClick="askSingleUpdate($el.dataset.updateKind, parseInt($el.dataset.updateId, 10), $el.dataset.updateName)"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        {{-- Bulk apply button (only when there are updatable items) --}}
        @if($totalCount > 0)
            <div class="flex justify-end">
                <x-form-button
                    type="button"
                    :label="__('admin/settings/systems/updates.apply_selected')"
                    variant="primary"
                    icon="fas fa-cloud-arrow-down"
                    x-bind:disabled="selectedCount === 0"
                    @click="if (selectedCount > 0) openModal('confirmSystemUpdatesModal')"
                />
            </div>

            <x-ui-modal
                id="confirmSystemUpdatesModal"
                :title="__('admin/settings/systems/updates.confirm.title')"
                message=""
                icon_type="info"
                confirm_color="blue"
                :confirm_label="__('admin/settings/systems/updates.apply_selected')"
                :cancel_label="__('common.cancel')"
                form="systemUpdatesApplyForm"
            >
                <p class="text-sm text-gray-700 dark:text-gray-300 text-center" x-text="`{{ __('admin/settings/systems/updates.confirm.message', ['count' => '%count%']) }}`.replace('%count%', selectedCount)"></p>
            </x-ui-modal>

            {{-- In-progress modal shown while the bulk apply request is in
                 flight. The submit button on confirmSystemUpdatesModal
                 carries the form="systemUpdatesApplyForm" attribute, so it
                 submits the bulk form; the form's @submit handler closes
                 the confirm modal and opens this one. The modal auto-
                 dismisses when the controller's redirect lands and the
                 page reloads — there is no in-page "completion" state to
                 manage. Matches the install wizard's installProgressModal
                 in style and behaviour. Reused by the per-row single-
                 update flow below — both bulk and single paths open this
                 same modal so the in-flight UX is identical. --}}
            <x-ui-modal
                id="updatesInProgressModal"
                :title="__('admin/settings/systems/updates.in_progress.title')"
                message=""
                icon_type="info"
                :dismissible="false"
                :closeOnly="true"
            >
                <p class="text-sm text-gray-700 dark:text-gray-300 text-center">
                    {{ __('admin/settings/systems/updates.in_progress.description_line1') }}<br>
                    {{ __('admin/settings/systems/updates.in_progress.description_line2') }}
                </p>
                <x-slot:footer>
                    <div class="flex items-center justify-center w-full py-1">
                        <i class="fas fa-spinner fa-spin text-indigo-500 text-xl"></i>
                    </div>
                </x-slot:footer>
            </x-ui-modal>

            {{-- Single-item confirm modal shared by every per-row "更新"
                 button. The target's display name is interpolated from
                 the outer scope's `pendingSingleUpdate.name`, populated
                 by askSingleUpdate() on click. The confirm button cannot
                 use the modal's built-in form="..." mode because the
                 target form is selected dynamically, so the footer slot
                 is overridden and submits `pendingSingleUpdate.formId`
                 directly via submitModalForm(). The confirm click also
                 closes this modal and opens updatesInProgressModal so
                 the single-update flow gets the same in-flight feedback
                 as the bulk-apply path. --}}
            <x-ui-modal
                id="confirmSingleUpdateModal"
                :title="__('admin/settings/systems/updates.single_confirm.title')"
                message=""
                icon_type="info"
                :cancel_label="__('common.cancel')"
            >
                <p class="text-sm text-gray-700 dark:text-gray-300 text-center"
                   x-text="`{{ __('admin/settings/systems/updates.single_confirm.message', ['name' => '%name%']) }}`.replace('%name%', pendingSingleUpdate.name || '')"></p>
                <x-slot:footer>
                    {{-- Buttons sit directly inside .modal-actions (which
                         is flex items-center justify-center) so they
                         centre to match the bulk-apply confirm modal's
                         default layout. The mx-2 spacing matches the
                         default footer's button gap. --}}
                    <x-form-button
                        type="button"
                        variant="secondary"
                        icon="fas fa-times"
                        :label="__('common.cancel')"
                        xDisabled="submitting"
                        xClick="close()"
                        class="mx-2"
                    />
                    <x-form-button
                        type="button"
                        variant="primary"
                        icon="fas fa-cloud-arrow-down"
                        :label="__('admin/settings/systems/updates.apply_one')"
                        xDisabled="submitting"
                        xClick="closeModal('confirmSingleUpdateModal'); submitting = true; openModal('updatesInProgressModal'); submitModalForm(pendingSingleUpdate.formId)"
                        class="mx-2"
                    />
                </x-slot:footer>
            </x-ui-modal>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                <i class="fas fa-check-circle text-3xl text-green-500 mb-2"></i>
                <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ __('admin/settings/systems/updates.all_up_to_date') }}</p>
            </div>
        @endif
    </form>
</div>
@endsection
