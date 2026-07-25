{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-auth.verification-notice />

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

@props([
    'resendRoute',
    'logoutRoute',
    'message',
    'resentMessage',
    'resendButtonText',
    'logoutButtonText',
])

<div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
    {{ $message }}
</div>

@if (session('resent') || session('status'))
    <x-ui-message
        type="success"
        :message="$resentMessage"
    />
@endif

<div class="mt-4 flex items-center justify-between">
    <form method="POST" action="{{ $resendRoute }}">
        @csrf
        <x-form-button
            type="submit"
            variant="primary"
            :label="$resendButtonText"
        />
    </form>

    <form method="POST" action="{{ $logoutRoute }}">
        @csrf
        <x-form-button
            type="submit"
            variant="secondary"
            :label="$logoutButtonText"
        />
    </form>
</div>
