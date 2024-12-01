@if (session('success'))
    <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
