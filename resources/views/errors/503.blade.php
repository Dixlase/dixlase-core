{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

Dual-licensed under the GNU Affero General Public License v3 or later
(together with the Dixlase Plugin and Theme Exception) or a commercial
license from exc-D inc. See LICENSE for details.
--}}

{{--
    503 error — service unavailable / maintenance mode.

    Served while the site is in maintenance (`php artisan down`).
    No theme delegation: the theme's view path may depend on DB
    or cached config that maintenance mode intentionally bypasses,
    so the minimal self-contained HTML is the only safe choice.

    For the lockdown-specific 503 (Dixlase lockdown subsystem)
    see `errors/lockdown.blade.php`.
--}}
@include('errors._minimal', [
    'code' => '503',
    'title' => app()->getLocale() === 'ja' ? 'メンテナンス中' : 'Under Maintenance',
    'message' => app()->getLocale() === 'ja'
        ? '現在メンテナンスを行っています。しばらくしてからもう一度お試しください。'
        : 'The site is currently undergoing maintenance. Please check back shortly.',
])
