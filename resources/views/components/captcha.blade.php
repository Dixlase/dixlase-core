{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-captcha />

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
    'enabled' => false,
    'widget' => null,
])

{{-- A preview draws no widget. A preview page is never submitted, so the
     widget would never be verified, and it would still cost the browser the
     provider's script and a third-party round trip on every re-render. The
     renderer declares the preview through CaptchaHelper::markPreviewRender();
     verification never reads that marker, so a form that really submits is
     unaffected. --}}
@if($enabled && $widget && ! \App\Helpers\CaptchaHelper::isPreviewRender())
    <div class="my-6 flex justify-center">
        {!! $widget !!}
    </div>
@endif
