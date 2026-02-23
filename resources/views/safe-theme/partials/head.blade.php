{{--
Safe Theme Head - 最小限のメタタグとコアCSSのみ
--}}

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ config('app.name', 'Dixlase') }}</title>

{{-- コアCSSのみ（テーマCSS/JSなし） --}}
{!! load_core_assets(['scss/style.scss'], 'common') !!}
