{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
# {{ __('mail.lockout_notification.title') }}

{{ __('mail.lockout_notification.message') }}

**{{ __('mail.lockout_notification.details') }}**

- **{{ __('mail.lockout_notification.identifier') }}:** {{ $details['identifier'] }}
- **{{ __('mail.lockout_notification.ip_address') }}:** {{ $details['ip_address'] }}
- **{{ __('mail.lockout_notification.user_agent') }}:** {{ $details['user_agent'] }}
- **{{ __('mail.lockout_notification.timestamp') }}:** {{ $details['timestamp'] }}

**{{ __('mail.lockout_notification.settings') }}**

- **{{ __('mail.lockout_notification.max_attempts') }}:** {{ $details['max_attempts'] }}{{ __('mail.lockout_notification.times') }}
- **{{ __('mail.lockout_notification.time_window') }}:** {{ $details['time_window'] }}{{ __('mail.lockout_notification.minutes') }}
- **{{ __('mail.lockout_notification.lockout_duration') }}:** {{ $details['lockout_duration'] }}{{ __('mail.lockout_notification.minutes') }}

{{ __('mail.lockout_notification.action_required') }}

{{ __('mail.lockout_notification.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
