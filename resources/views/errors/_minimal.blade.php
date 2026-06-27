{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dual-licensed under the GNU Affero General Public License v3 or later
(together with the Dixlase Plugin and Theme Exception) or a commercial
license from exc-D inc. See LICENSE for details.
--}}

{{--
    Minimal HTML error page — no theme / Vite / Tailwind / DB dependency.
    Used as the bulletproof core fallback when:
      - the active theme has no `themes::errors.{code}` template, OR
      - the active theme itself is broken (500 case) and rendering its
        error template would re-trigger the original error.

    Variables expected:
      $code    string|int    "404", "500", etc.
      $title   string        Short label (e.g. "Page Not Found")
      $message string        One-sentence prose explanation
--}}
@php
    $isJa = app()->getLocale() === 'ja';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} — {{ $title }}</title>
    <style @cspNonce>
        :root {
            --bg: #ffffff;
            --fg: #1a1d24;
            --fg-muted: #5b6473;
            --border: rgba(15, 17, 23, 0.10);
            --accent: #3b82f6;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #08080c;
                --fg: #e7eaf0;
                --fg-muted: #9aa3b2;
                --border: rgba(255, 255, 255, 0.10);
                --accent: #60a5fa;
            }
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            background: var(--bg);
            color: var(--fg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .wrap {
            max-width: 32rem;
            width: 100%;
            text-align: center;
        }
        .code {
            font-size: 5rem;
            font-weight: 700;
            line-height: 1;
            margin: 0 0 1rem;
            letter-spacing: -0.02em;
        }
        .title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 1rem;
        }
        .message {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--fg-muted);
            margin: 0 0 2rem;
        }
        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        a {
            color: var(--accent);
            text-decoration: none;
            padding: 0.625rem 1.25rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-weight: 500;
            transition: border-color 160ms ease, color 160ms ease;
        }
        a:hover {
            border-color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="wrap">
        <p class="code">{{ $code }}</p>
        <h1 class="title">{{ $title }}</h1>
        <p class="message">{{ $message }}</p>
        @if($code !== '503')
            <div class="actions">
                <a href="{{ url('/') }}">{{ $isJa ? 'ホームへ戻る' : 'Back to Home' }}</a>
            </div>
        @endif
    </div>
</body>
</html>
