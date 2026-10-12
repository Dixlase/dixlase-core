{{-- Smoke-test theme stub for `themes::admin.preview-frame`.

AdminFrontController::previewFrame() renders this view. In production
the active theme supplies it; the test harness does not load a theme,
so without this stub the preview-frame GET route 500s on a missing view
-- which is a test-env gap, not a product defect. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>preview-frame-stub</title>
</head>
<body></body>
</html>
