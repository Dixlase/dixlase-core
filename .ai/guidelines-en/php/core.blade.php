# PHP

@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
@if($assist->shouldEnforceStrictTypes())
- Always declare strict typing at the top of `.php` files: `declare(strict_types=1);`
@endif
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
@if(empty($assist->enums()) || preg_match('/[A-Z]{3,8}/', $assist->enumContents()))
- Enum keys should typically be TitleCase. Example: `FavoritePerson`, `BestLake`, `Monthly`
@else
- Enum keys should follow existing Enum conventions in the application
@endif

## Comments
- Prefer PHPDoc blocks over inline comments. Do not write comments in code unless the logic is very complex

## PHPDoc Blocks
- Add appropriate array shape type definitions for arrays
