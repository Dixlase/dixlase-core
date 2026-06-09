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

<x-mail::message>
# {{ __('mail.two-fa.device.title') }}

{{ __('mail.two-fa.device.greeting', ['name' => $user->display_name ?? $user->account_name ?? $user->name ?? $user->email]) }}

{{ __('mail.two-fa.device.message') }}

## {{ __('mail.two-fa.details_title') }}

- **{{ __('mail.two-fa.ip_address') }}**: {{ $ipAddress }}
- **{{ __('mail.two-fa.user_agent') }}**: {{ $userAgent }}
- **{{ __('mail.two-fa.timestamp') }}**: {{ $timestamp }}

{{ __('mail.two-fa.device.action_prompt') }}

<x-mail::button :url="$approveUrl" color="success">
{{ __('mail.two-fa.device.approve_button') }}
</x-mail::button>

<x-mail::button :url="$denyUrl" color="error">
{{ __('mail.two-fa.device.deny_button') }}
</x-mail::button>

{{ __('mail.two-fa.security_notice') }}

{{ __('mail.two-fa.regards') }}<br><br>
{{ config('app.name') }}
</x-mail::message>
