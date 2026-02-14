# Laravel 12

- Important: Always use the `search-docs` tool to get version-specific Laravel documentation and the latest code examples
@if (file_exists(base_path('app/Http/Kernel.php')))
- This project was upgraded from Laravel 10 but has not migrated to the new simplified file structure
- This is fine and recommended by Laravel. Follow the existing Laravel 10 structure. Do not migrate to the new Laravel structure unless the user explicitly requests it

## Laravel 10 Structure
- Middleware is typically placed in `app/Http/Middleware/`, service providers in `app/Providers/`
- The Laravel 10 structure does not use `bootstrap/app.php` for application configuration:
    - Middleware registration is done in `app/Http/Kernel.php`
    - Exception handling is done in `app/Exceptions/Handler.php`
    - Console commands and schedules are registered in `app/Console/Kernel.php`
    - Rate limiting is often defined in `RouteServiceProvider` or `app/Http/Kernel.php`
@else
- Since Laravel 11, a new simplified file structure has been adopted, and this project uses it

## Laravel 12 Structure
- In Laravel 12, middleware is not registered in `app/Http/Kernel.php`
- Middleware is configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`
- `bootstrap/app.php` is where middleware, exceptions, and routing files are registered
- Application-specific service providers are listed in `bootstrap/providers.php`
- `app\Console\Kernel.php` does not exist. Use `bootstrap/app.php` or `routes/console.php` for console configuration
- Console commands in `app/Console/Commands/` are automatically available without manual registration
@endif

## Database
- When modifying columns in migrations, include all previously defined attributes for that column. Attributes not included will be dropped
- Laravel 12 natively supports limiting Eager Load record counts without external packages: `$query->latest()->limit(10);`

### Models
- Configure casts using the `casts()` method, not the `$casts` property. Follow existing conventions in other models
