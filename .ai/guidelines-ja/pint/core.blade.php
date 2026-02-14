@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Pint コードフォーマッター

@if($assist->supportsPintAgentFormatter())
- 変更を確定する前に `{{ $assist->binCommand('pint') }} --dirty --format agent` を実行して、プロジェクトのコードスタイルに一致させること
- `{{ $assist->binCommand('pint') }} --test --format agent` は実行しない。フォーマット修正には `{{ $assist->binCommand('pint') }} --format agent` を使用する
@else
- 変更を確定する前に `{{ $assist->binCommand('pint') }} --dirty` を実行して、プロジェクトのコードスタイルに一致させること
- `{{ $assist->binCommand('pint') }} --test` は実行しない。フォーマット修正には `{{ $assist->binCommand('pint') }}` を使用する
@endif
