<x-mail::message>
# {{ $isUnhealthyWarning ? __('mail.extension_operation.subject_unhealthy_warning', ['app_name' => config('app.name'), 'type' => __('mail.extension_operation.type_' . $details['type'])]) : __('mail.extension_operation.message_' . $operation, ['type' => __('mail.extension_operation.type_' . $details['type']), 'name' => $details['name']]) }}

{{ __('mail.extension_operation.greeting') }}

@if($isUnhealthyWarning)
{{ __('mail.extension_operation.message_unhealthy_warning', ['type' => __('mail.extension_operation.type_' . $details['type'])]) }}
@else
{{ __('mail.extension_operation.message_' . $operation, ['type' => __('mail.extension_operation.type_' . $details['type']), 'name' => $details['name']]) }}
@endif

**{{ __('mail.extension_operation.details_title') }}**

- **{{ __('mail.extension_operation.extension_name') }}:** {{ $details['name'] }}
- **{{ __('mail.extension_operation.extension_type') }}:** {{ __('mail.extension_operation.type_' . $details['type']) }}
- **{{ __('mail.extension_operation.operation') }}:** {{ __('mail.extension_operation.operation_' . $operation) }}
- **{{ __('mail.extension_operation.operated_by') }}:** {{ $details['operated_by'] ?? __('common.unknown') }}
- **{{ __('mail.extension_operation.operated_at') }}:** {{ $details['operated_at'] ?? now()->format('Y-m-d H:i:s') }}
@if(isset($details['version']))
- **{{ __('mail.extension_operation.version') }}:** {{ $details['version'] }}
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
- **{{ __('mail.extension_operation.health_status') }}:** {{ __('mail.extension_operation.' . $healthLabel) }}
@endif

@if($isUnhealthyWarning && isset($details['health_status']))
@php
$healthLevelLabel = match($details['health_status']) {
    'low' => __('mail.extension_operation.health_healthy'),
    'medium' => __('mail.extension_operation.health_warning'),
    'high' => __('mail.extension_operation.health_needs_attention'),
    default => __('mail.extension_operation.health_not_verified'),
};
@endphp
<x-mail::panel>
⚠️ {{ __('mail.extension_operation.unhealthy_notice', ['level' => $healthLevelLabel]) }}
</x-mail::panel>
@endif

---

<small>{{ __('mail.extension_operation.auto_notification') }}</small>

{{ __('mail.extension_operation.regards') }}<br>
{{ config('app.name') }}
</x-mail::message>
