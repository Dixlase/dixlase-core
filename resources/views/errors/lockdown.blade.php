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

{{-- ロックダウン中の 503 エラーページ --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable</title>
</head>
<body>
    <div style="max-width:600px;margin:80px auto;font-family:sans-serif;padding:24px;">
        <h1>{{ __('admin/lockdown.title', [], 'en') ?? 'Service Temporarily Unavailable' }}</h1>
        <p>{{ $message ?? 'The site is currently under lockdown.' }}</p>
        @if(isset($lockdown) && $lockdown->type)
            <p style="color:#888;font-size:0.9em;">Type: {{ $lockdown->type }}</p>
        @endif
    </div>
</body>
</html>
