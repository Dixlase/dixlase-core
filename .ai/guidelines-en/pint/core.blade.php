@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Pint Code Formatter

@if($assist->supportsPintAgentFormatter())
- Run `{{ $assist->binCommand('pint') }} --dirty --format agent` before finalizing changes to match the project's code style
- Do not run `{{ $assist->binCommand('pint') }} --test --format agent`. Use `{{ $assist->binCommand('pint') }} --format agent` to fix formatting
@else
- Run `{{ $assist->binCommand('pint') }} --dirty` before finalizing changes to match the project's code style
- Do not run `{{ $assist->binCommand('pint') }} --test`. Use `{{ $assist->binCommand('pint') }}` to fix formatting
@endif
