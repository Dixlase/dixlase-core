{{-- Smoke-test theme stub.

Preview-mode admin views extend `themes::layouts.app`, which production
supplies via whatever theme is active. The test harness does not load
a theme, so without this stub `admin.front.preview-frame` and friends
500 on an unregistered namespace -- which is a test-env gap, not a
product defect the suite is trying to find.

Keep this minimal: the smoke test checks `status < 500`, not markup. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'test-theme-stub')</title>
</head>
<body>
    @yield('content')
</body>
</html>
