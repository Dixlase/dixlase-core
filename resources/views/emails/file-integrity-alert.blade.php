{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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
