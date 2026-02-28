<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('maintenance.title') }}</title>
    <link rel="stylesheet" href="/assets/css/maintenance.css">
</head>
<body>
    <div class="container">
        @if(isset($isPreview) && $isPreview)
            <div class="preview-badge">
                {{ __('maintenance.preview_mode') }}
            </div>
        @endif

        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/>
            </svg>
        </div>

        <h1>{{ __('maintenance.title') }}</h1>

        <div class="message">{{ $message }}</div>

        @if(isset($releaseAt) && $releaseAt)
            <div class="release-info">
                <p>{{ __('maintenance.expected_release') }}</p>
                <div class="release-time">
                    {{ \Carbon\Carbon::parse($releaseAt)->format('Y年m月d日 H:i') }}
                </div>
            </div>
        @endif
    </div>
</body>
</html>
