@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Follow the Laravel Way

- Use `{{ $assist->artisanCommand('make:') }}` commands for creating new files (migrations, controllers, models, etc.). Check available Artisan commands with the `list-artisan-commands` tool
- Use `{{ $assist->artisanCommand('make:class') }}` for creating generic PHP classes
- Pass `--no-interaction` to all Artisan commands to run without user input. Also specify the correct `--options`

## Database
- Always use Eloquent relation methods with return type hints. Prefer relation methods over raw queries or manual joins
- Use Eloquent models and relations before raw database queries
- Avoid `DB::` and prefer `Model::query()`. Generate code that leverages ORM features
- Generate code that uses Eager Loading to prevent N+1 query problems
- Use Laravel's query builder for very complex database operations

### Model Creation
- When creating a new model, also create a factory and seeder. Ask the user if anything else is needed, and check `{{ $assist->artisanCommand('make:model') }}` options with `list-artisan-commands`

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
- Use `{{ $assist->artisanCommand('make:test [options] {name}') }}` for creating tests. Feature tests are the default; pass `--unit` for unit tests

## Vite Errors
- If you get an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, run `{{ $assist->nodePackageManagerCommand('run build') }}` or ask the user to run `{{ $assist->nodePackageManagerCommand('run dev') }}` or `{{ $assist->composerCommand('run dev') }}`
