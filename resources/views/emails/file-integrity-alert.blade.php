<x-mail::message>
# {{ __('mail.file_integrity.title') }}

{{ __('mail.file_integrity.greeting', ['site_name' => $siteName]) }}

{{ __('mail.file_integrity.intro', ['status' => $statusLabel]) }}

<x-mail::panel>
**{{ __('mail.file_integrity.scan_info') }}**

- **{{ __('mail.file_integrity.scan_date') }}:** {{ $audit->created_at->format('Y-m-d H:i:s') }}
- **{{ __('mail.file_integrity.status') }}:** {{ $statusLabel }}
- **{{ __('mail.file_integrity.files_scanned') }}:** {{ $audit->total_files_scanned }}
</x-mail::panel>

@if(!empty($summary))
## {{ __('mail.file_integrity.issues_summary') }}

@if(($summary['changed'] ?? 0) > 0)
- **{{ __('mail.file_integrity.changed_files') }}:** {{ $summary['changed'] }}
@endif

@if(($summary['added'] ?? 0) > 0)
- **{{ __('mail.file_integrity.added_files') }}:** {{ $summary['added'] }}
@endif

@if(($summary['removed'] ?? 0) > 0)
- **{{ __('mail.file_integrity.removed_files') }}:** {{ $summary['removed'] }}
@endif

@if(($summary['suspicious'] ?? 0) > 0)
- **{{ __('mail.file_integrity.suspicious_files') }}:** {{ $summary['suspicious'] }}
@endif
@endif

{{ __('mail.file_integrity.action_required') }}

@if($siteUrl)
<x-mail::button :url="$siteUrl . '/admin/settings/security/integrity'">
{{ __('mail.file_integrity.view_details_button') }}
</x-mail::button>
@endif

{{ __('mail.file_integrity.thanks') }}<br>
{{ $siteName }}
</x-mail::message>
