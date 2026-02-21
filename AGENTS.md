<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.24
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v11
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Git Commit Messages

- Do not include `Co-Authored-By` lines in commit messages.
- Use conventional commit format (e.g. `feat:`, `fix:`, `refactor:`).
- Write the commit message in **bilingual format**.
- Place both English and Japanese titles consecutively at the top, followed by English bullet points, a `----` separator, then Japanese bullet points.
- Example:
  ```
  feat: add user profile page
  feat: ユーザープロフィールページを追加

  - Add ProfileController with show/edit actions
  - Create profile Blade views with avatar upload

  ----

  - ProfileControllerにshow/editアクションを追加
  - アバターアップロード付きプロフィールBladeビューを作成
  ```

## View Logic Separation Rules

- Do not directly reference `\App\Enums\*`, `\App\Helpers\*`, `\App\Services\*`, `\App\Models\*` classes in Blade templates.
- Prepare all enum values/labels/option arrays, helper results, and service results in controllers and pass them as view variables.
- Blade components receive necessary data as props (injected from parent views/controllers).
- Exception: Helper calls in shared full-screen partials (e.g. sidebar) are allowed.
- **`@php` blocks are forbidden in admin Blade views** (except shared full-screen partials like sidebar).
  - Business logic, data transformations, and config array building must be done in controllers or Presenters.
  - Allowed exceptions: inline expressions like `{{ $var ?? 'default' }}`, inline use of `old()` helper, simple variable assignments like `@php $appearanceValue = old('appearance', ...) @endphp`.

## Translation Key Scoping

- `lang/*/admin/navigation.php` is a sidebar-only translation file. Do not reference it from non-sidebar views.
- Each view defines its translation keys in its own translation file (e.g. `lang/*/admin/settings/base/index.php`).
- Components define their translation keys in `lang/*/components/<component-name>.php`.

### Plugin Translation File Structure

- Plugins use a directory-based structure: `lang/{en,ja}/admin/{resource}/{page}.php`.
- `admin.php` should only contain plugin info (plugin, provider). Page-specific translations go in separate files.
- Each admin page translation file must have `heading` and `description` keys at the top:
  ```php
  return [
      'heading' => 'Page Master',
      'description' => 'Manage all pages. ...',
      // other keys
  ];
  ```
- `heading`: Auto-resolved by `resolvePluginHeadingKey()` (converts route name to directory path).
- `description`: A 1-2 sentence description of what can be done on that page.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server with powerful tools specifically for this application. Use it actively

## Artisan

- When running Artisan commands, check available parameters with the `list-artisan-commands` tool

## URL

- When sharing project URLs with the user, verify the correct scheme, domain/IP, and port with the `get-absolute-url` tool

## Tinker / Debugging

- Use the `tinker` tool for debugging PHP code and directly querying Eloquent models
- Use the `database-query` tool when you only need to read from the database
- Check table structure with the `database-schema` tool before creating migrations or models

## Reading Browser Logs (`browser-logs` tool)

- Use the `browser-logs` tool to read browser logs, errors, and exceptions
- Only recent browser logs are useful — ignore old logs

## Documentation Search (Important)

- Boost has a powerful `search-docs` tool — use it before other approaches when working with Laravel ecosystem packages. This tool automatically sends installed packages and versions to the Boost API and returns only version-specific documentation. Pass an array of package names when you need documentation for specific packages
- Search documentation before making code changes to verify the correct approach
- Use multiple broad, simple topic-based queries at once. Example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results are returned first
- Do not include package names in queries (package information is already shared). Example: use `test resource table`, not `filament 4 test resource table`

### Search Syntax

1. Word search with auto-stemming - query=authentication - also finds 'authenticate' and 'auth'
2. Multiple words (AND logic) - query=rate limit - results containing "rate" AND "limit"
3. Quoted phrases (exact match) - query="infinite scroll" - adjacent words in this order
4. Mixed queries - query=middleware "rate limit" - "middleware" AND exact match "rate limit"
5. Multiple queries - queries=["authentication", "middleware"] - either term

=== php rules ===

# PHP

- Always use curly braces in control structures, even for single-line statements

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty zero-parameter `__construct()` unless the constructor is private

## Type Declarations

- Always declare explicit return types for methods and functions
- Use appropriate PHP type hints for method parameters

<!-- Example of explicit return types and method parameters -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enum

- Enum keys should typically be TitleCase. Example: `FavoritePerson`, `BestLake`, `Monthly`

## Comments

- Prefer PHPDoc blocks over inline comments. Do not write comments in code unless the logic is very complex

## PHPDoc Blocks

- Add appropriate array shape type definitions for arrays

=== tests rules ===

# Enforce Testing

- All changes must be tested programmatically. Create new tests or update existing tests, and verify that the relevant tests pass
- Run only the minimum necessary tests to ensure code quality and speed. Use `php artisan test --compact` with file names or filters

=== laravel/core rules ===

# Follow the Laravel Way

- Use `php artisan make:` commands for creating new files (migrations, controllers, models, etc.). Check available Artisan commands with the `list-artisan-commands` tool
- Use `php artisan make:class` for creating generic PHP classes
- Pass `--no-interaction` to all Artisan commands to run without user input. Also specify the correct `--options`

## Database

- Always use Eloquent relation methods with return type hints. Prefer relation methods over raw queries or manual joins
- Use Eloquent models and relations before raw database queries
- Avoid `DB::` and prefer `Model::query()`. Generate code that leverages ORM features
- Generate code that uses Eager Loading to prevent N+1 query problems
- Use Laravel's query builder for very complex database operations

### Model Creation

- When creating a new model, also create a factory and seeder. Ask the user if anything else is needed, and check `php artisan make:model` options with `list-artisan-commands`

### API and Eloquent Resources

- Use Eloquent API Resources and API versioning by default for APIs. Follow existing conventions if API routes use a different pattern

## Controllers and Validation

- Always validate with Form Request classes, not inline in controllers. Include both validation rules and custom error messages
- Check existing Form Requests to determine whether to use array-based or string-based rules

## Authentication and Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.)

## URL Generation

- Prefer named routes and the `route()` function for generating links to other pages

## Queues

- Use queued jobs with the `ShouldQueue` interface for time-consuming operations

## Configuration

- Use environment variables only in config files — do not use the `env()` function directly outside config files. Use `config('app.name')` instead of `env('APP_NAME')`

## Testing

- Use factories for creating test models. Check existing factory custom states before manual setup
- Faker: use `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions for whether to use `$this->faker` or `fake()`
- Use `php artisan make:test [options] {name}` for creating tests. Feature tests are the default; pass `--unit` for unit tests

## Vite Errors

- If you get an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`

=== laravel/v12 rules ===

# Laravel 12

- Important: Always use the `search-docs` tool to get version-specific Laravel documentation and the latest code examples
- Since Laravel 11, a new simplified file structure has been adopted, and this project uses it

## Laravel 12 Structure

- In Laravel 12, middleware is not registered in `app/Http/Kernel.php`
- Middleware is configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`
- `bootstrap/app.php` is where middleware, exceptions, and routing files are registered
- Application-specific service providers are listed in `bootstrap/providers.php`
- `app\Console\Kernel.php` does not exist. Use `bootstrap/app.php` or `routes/console.php` for console configuration
- Console commands in `app/Console/Commands/` are automatically available without manual registration

## Database

- When modifying columns in migrations, include all previously defined attributes for that column. Attributes not included will be dropped
- Laravel 12 natively supports limiting Eager Load record counts without external packages: `$query->latest()->limit(10);`

### Models

- Configure casts using the `casts()` method, not the `$casts` property. Follow existing conventions in other models

=== livewire/core rules ===

# Livewire

- Livewire lets you build dynamic, reactive interfaces using only PHP — no JavaScript required
- Instead of a JavaScript framework, use Alpine.js for building UIs that need client-side interaction
- Keep state on the server and let the UI reflect it. Perform validation and authorization in actions (just like HTTP requests)
- Important: Always activate `livewire-development` when working on Livewire-related tasks

=== pint/core rules ===

# Laravel Pint Code Formatter

- Run `vendor/bin/pint --dirty` before finalizing changes to match the project's code style
- Do not run `vendor/bin/pint --test`. Use `vendor/bin/pint` to fix formatting

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create new tests
- If you find tests written in "Pest", convert them to PHPUnit
- After updating a test, run that individual test
- Once feature-related tests pass, ask the user whether to run the full test suite
- Tests should cover all happy paths, failure paths, and edge cases
- Do not delete tests or test files from the tests directory without approval. These are core parts of the application, not temporary files

## Running Tests

- Run minimal tests with filters before finalizing
- Run all tests: `php artisan test --compact`
- Run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`
- Filter by specific test name: `php artisan test --compact --filter=testName` (recommended after changing related files)

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions. Check existing patterns in the project before adding new ones
- Important: Always use the `search-docs` tool to get version-specific Tailwind CSS documentation and the latest code examples. Do not rely on training data
- Important: Always activate `tailwindcss-development` when working on Tailwind CSS or styling-related tasks
</laravel-boost-guidelines>
