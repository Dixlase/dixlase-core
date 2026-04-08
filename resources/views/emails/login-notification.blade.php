{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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

<x-mail::message>
# {{ __('mail.login_notification.title') }}

{{ $toSystem ? __('mail.login_notification.system_message') : __('mail.login_notification.user_message', ['name' => $user->display_name ?? $user->account_name ?? $user->name]) }}

**{{ __('mail.login_notification.details_title') }}**

- **{{ __('mail.login_notification.datetime') }}** {{ $datetime }}
- **{{ __('mail.login_notification.ip_address') }}** {{ $ip }}

@if ($toSystem)
- **{{ __('mail.login_notification.user_agent') }}** {{ $ua }}
- **{{ __('mail.login_notification.user_id') }}** {{ $user->id }}
@endif

@if (!$toSystem)
{{ __('mail.login_notification.security_notice') }}

<x-mail::button :url="config('app.url')" color="primary">
{{ __('mail.login_notification.access_site') }}
</x-mail::button>
@endif

{{ __('mail.login_notification.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>