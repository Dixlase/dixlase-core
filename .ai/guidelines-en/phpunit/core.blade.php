@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# PHPUnit

- This application uses PHPUnit. All tests must be written as PHPUnit classes. Use `{{ $assist->artisanCommand('make:test --phpunit {name}') }}` to create new tests
- If you find tests written in "Pest", convert them to PHPUnit
- After updating a test, run that individual test
- Once feature-related tests pass, ask the user whether to run the full test suite
- Tests should cover all happy paths, failure paths, and edge cases
- Do not delete tests or test files from the tests directory without approval. These are core parts of the application, not temporary files

## Running Tests
- Run minimal tests with filters before finalizing
- Run all tests: `{{ $assist->artisanCommand('test --compact') }}`
- Run all tests in a file: `{{ $assist->artisanCommand('test --compact tests/Feature/ExampleTest.php') }}`
- Filter by specific test name: `{{ $assist->artisanCommand('test --compact --filter=testName') }}` (recommended after changing related files)
