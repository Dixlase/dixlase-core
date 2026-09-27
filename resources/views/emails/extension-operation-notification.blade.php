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
# {{ $isUnhealthyWarning ? __('mail.extension.subject_unhealthy_warning', ['app_name' => config('app.name'), 'type' => __('mail.extension.type_' . $details['type'])]) : __('mail.extension.message_' . $operation, ['type' => __('mail.extension.type_' . $details['type']), 'name' => $details['name']]) }}

{{ __('mail.extension.greeting') }}

@if($isUnhealthyWarning)
{{ __('mail.extension.message_unhealthy_warning', ['type' => __('mail.extension.type_' . $details['type'])]) }}
@else
{{ __('mail.extension.message_' . $operation, ['type' => __('mail.extension.type_' . $details['type']), 'name' => $details['name']]) }}
@endif

**{{ __('mail.extension.details_title') }}**

- **{{ __('mail.extension.extension_name') }}:** {{ $details['name'] }}
- **{{ __('mail.extension.extension_type') }}:** {{ __('mail.extension.type_' . $details['type']) }}
- **{{ __('mail.extension.operation') }}:** {{ __('mail.extension.operation_' . $operation) }}
- **{{ __('mail.extension.operated_by') }}:** {{ $details['operated_by'] ?? __('common.unknown') }}
- **{{ __('mail.extension.operated_at') }}:** {{ $details['operated_at'] ?? now()->format('Y-m-d H:i:s') }}
@if(isset($details['version']))
- **{{ __('mail.extension.version') }}:** {{ $details['version'] }}
@endif
@if(isset($details['health_status']))
@php
$healthLabels = [
    'low' => 'health_healthy',
    'medium' => 'health_warning',
    'high' => 'health_needs_attention',
    'unknown' => 'health_not_verified',
];
$healthLabel = $healthLabels[$details['health_status']] ?? 'health_not_verified';
@endphp
- **{{ __('mail.extension.health_status') }}:** {{ __('mail.extension.' . $healthLabel) }}
@endif

@if($isUnhealthyWarning && isset($details['health_status']))
@php
$healthLevelLabel = match($details['health_status']) {
    'low' => __('mail.extension.health_healthy'),
    'medium' => __('mail.extension.health_warning'),
    'high' => __('mail.extension.health_needs_attention'),
    default => __('mail.extension.health_not_verified'),
};
@endphp
<x-mail::panel>
⚠️ {{ __('mail.extension.unhealthy_notice', ['level' => $healthLevelLabel]) }}
</x-mail::panel>
@endif

---

<small>{{ __('mail.extension.auto_notification') }}</small>

{{ __('mail.extension.regards') }}<br>
{{ config('app.name') }}
</x-mail::message>
