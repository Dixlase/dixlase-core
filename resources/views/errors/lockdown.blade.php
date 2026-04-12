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
