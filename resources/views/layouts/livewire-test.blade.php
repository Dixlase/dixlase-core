<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Livewire Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-4">Livewire Test Layout</h1>
        
        @yield('content')
    </div>

    @livewireScripts
    <script>
        console.log('[Test Layout] Livewire loaded');
        console.log('[Test Layout] Livewire.all():', typeof Livewire !== 'undefined' ? Livewire.all() : 'Livewire not found');
    </script>
</body>
</html>
