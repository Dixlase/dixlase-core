<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('maintenance.title') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 48px 32px;
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }
        h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 16px;
        }
        .message {
            font-size: 18px;
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 24px;
            white-space: pre-wrap;
        }
        .release-info {
            background: #f7fafc;
            border-radius: 8px;
            padding: 16px;
            margin-top: 24px;
        }
        .release-info p {
            font-size: 14px;
            color: #718096;
            margin-bottom: 8px;
        }
        .release-time {
            font-size: 20px;
            font-weight: 600;
            color: #667eea;
        }
        .preview-badge {
            display: inline-block;
            background: #fbbf24;
            color: #78350f;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
        }
    </style>
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
