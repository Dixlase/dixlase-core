{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-admin.translate-button />

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
Translate button for an admin edit screen.

Asks every provider of the TranslationUiProviderInterface capability for
the translation editor of this record and links to the first that
answers. Renders nothing when the record is not saved yet, when no
provider is installed, or when no provider translates this content type,
so an edit screen embeds it without a guard.

type          Content type, `{extension-slug}:{type}`
id            Primary key of the record; null on a create form
sourceLocale  Language the record is written in; defaults to the admin UI locale
--}}

@props([
    'type',
    'id' => null,
    'sourceLocale' => null,
])

@php
    $translateUrl = null;

    if ($id !== null && $id !== '') {
        foreach (app(\App\Services\Plugin\PluginServiceResolver::class)->resolveAll(\App\Contracts\PluginIntegration\TranslationUiProviderInterface::class) as $result) {
            if ($result->isResolved()) {
                $translateUrl = $result->instance->getTranslationEditUrl($type, $id);
                if ($translateUrl !== null) {
                    break;
                }
            }
        }
    }

    if ($translateUrl !== null) {
        $sourceLocaleName = \App\Helpers\LocaleHelper::getLocaleName(
            is_string($sourceLocale) && $sourceLocale !== '' ? $sourceLocale : app()->getLocale(),
            false,
        );
    }
@endphp

@if ($translateUrl !== null)
    <x-form-button
        type="link"
        :href="$translateUrl"
        icon="fas fa-language"
        variant="secondary"
        size="sm"
        class="w-full"
    >
        {{ __('components/admin/translate-button.label') }}
    </x-form-button>
    <x-form-help-text
        :text="e(__('components/admin/translate-button.help', ['language' => $sourceLocaleName]))"
    />
@endif
