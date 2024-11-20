<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {{ $exception->getStatusCode() }}</title>
</head>
<body>
    <h1>Error {{ $exception->getStatusCode() }}</h1>
    <p>{{ $exception->getMessage() ?: 'An error occurred' }}</p>
</body>
</html>
