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

@props([
    'items' => [],
])

<div {{ $attributes->merge(['class' => 'barometer']) }}>
    @foreach($items as $index => $item)
        @php
            $status = $item['status'] ?? 'unknown';
            $tier = $item['tier'] ?? 'default';
            $isFirst = $index === 0;
            $isLast = $index === count($items) - 1;

            $statusClass = match(true) {
                $status === 'unknown' => 'barometer__segment--unknown',
                $status === 'ng' => 'barometer__segment--ng',
                default => 'barometer__segment--' . $tier,
            };
            $positionClass = $isFirst ? 'barometer__segment--first' : ($isLast ? 'barometer__segment--last' : '');
        @endphp
        <div class="barometer__segment {{ $statusClass }} {{ $positionClass }}">
            <span class="barometer__label">{{ $item['label'] }}</span>
        </div>
    @endforeach
</div>
