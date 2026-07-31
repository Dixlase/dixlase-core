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

{{--
Demo read-only guard.

When DIXLASE_DEMO_MODE is on, DemoGuard blocks the POST for a set of
destructive / lockout-prone admin routes. Without a visual cue those forms
still look editable and only fail on submit. This component makes that
explicit: it disables the controls of any <form> whose action targets a
blocked route and prepends a "view-only in the demo" notice, so visitors can
see the setting is locked. Super admins bypass DemoGuard, so their forms stay
live and this component renders nothing for them. The blocklist is read from
DemoGuard so this stays in lock-step with the actual server-side enforcement.
--}}
@php
    $demoUser = auth()->user();
    $demoIsSuperAdmin = $demoUser
        && $demoUser->role instanceof \App\Enums\MemberRole
        && $demoUser->role === \App\Enums\MemberRole::SUPER_ADMIN;
    $demoReadonlyActive = config('dixlase.demo_mode') && $demoUser && ! $demoIsSuperAdmin;

    // Resolve each blocked route name to its concrete URI (the admin prefix is
    // already baked in at registration time). "*.*" entries expand to every
    // registered route under that namespace.
    $demoBlockedUris = [];
    if ($demoReadonlyActive) {
        $byName = \Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName();
        foreach (\App\Http\Middleware\DemoGuard::blockedRouteNames() as $entry) {
            $names = [];
            if (\Illuminate\Support\Str::endsWith($entry, '.*')) {
                $prefix = substr($entry, 0, -2);
                foreach ($byName as $rname => $route) {
                    if ($rname === $prefix || \Illuminate\Support\Str::startsWith($rname, $prefix . '.')) {
                        $names[] = $rname;
                    }
                }
            } else {
                $names[] = $entry;
            }
            foreach ($names as $n) {
                if (($route = $byName[$n] ?? null) !== null) {
                    $demoBlockedUris['/' . ltrim($route->uri(), '/')] = true;
                }
            }
        }
        $demoBlockedUris = array_keys($demoBlockedUris);
    }
@endphp

@if($demoReadonlyActive && ! empty($demoBlockedUris))
    {{-- Notice cloned by the script into each locked form (reuses ui-message). --}}
    <template id="demo-readonly-notice-tpl">
        <div class="mb-4" data-demo-readonly-notice>
            <x-ui-message type="info" :message="__('admin/demo.readonly_notice')" />
        </div>
    </template>

    <script @cspNonce>
        (function () {
            var uris = @json($demoBlockedUris, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Build an anchored path regex per URI: literal segments are escaped,
            // {param} / {param?} segments match a single path segment.
            var regexes = uris.map(function (uri) {
                var parts = uri.split('/').map(function (seg) {
                    if (/^\{.+\??\}$/.test(seg)) {
                        return '[^/]+';
                    }
                    return seg.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                });
                return new RegExp('^' + parts.join('/') + '/?$');
            });

            function pathOf(action) {
                try {
                    return new URL(action || window.location.href, window.location.origin).pathname;
                } catch (e) {
                    return null;
                }
            }

            function isBlocked(path) {
                if (!path) {
                    return false;
                }
                for (var i = 0; i < regexes.length; i++) {
                    if (regexes[i].test(path)) {
                        return true;
                    }
                }
                return false;
            }

            function lockForm(form) {
                if (form.hasAttribute('data-demo-locked')) {
                    return;
                }
                form.setAttribute('data-demo-locked', '');

                var tpl = document.getElementById('demo-readonly-notice-tpl');
                if (tpl && 'content' in tpl) {
                    form.insertBefore(tpl.content.cloneNode(true), form.firstChild);
                }

                // Disable interactive controls. Hidden inputs are left intact
                // (they carry no user input and the form can't submit anyway).
                form.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                    if (el.type === 'hidden') {
                        return;
                    }
                    el.disabled = true;
                    el.setAttribute('aria-disabled', 'true');
                });
            }

            function scan() {
                document.querySelectorAll('form').forEach(function (form) {
                    if (isBlocked(pathOf(form.getAttribute('action')))) {
                        lockForm(form);
                    }
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', scan);
            } else {
                scan();
            }
        })();
    </script>
@endif
