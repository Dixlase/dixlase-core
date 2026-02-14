@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Enforce Testing

- All changes must be tested programmatically. Create new tests or update existing tests, and verify that the relevant tests pass
- Run only the minimum necessary tests to ensure code quality and speed. Use `{{ $assist->artisanCommand('test --compact') }}` with file names or filters
