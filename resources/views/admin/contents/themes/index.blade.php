@extends('admin::partials.layout')

@section('content')

        <!-- Flash message for success or error -->
        @include('components::flash_message')

        <form action="{{ route('admin.contents.themes.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="theme" required>
            <button type="submit">アップロード</button>
        </form>

        <ul>
            @foreach ($themes as $theme)
                <li>
                    {{ $theme->name }} - {{ $theme->version }}
                    @if ($theme->is_active)
                        <strong>(現在使用中)</strong>
                    @else
                        <form action="{{ route('theme.switch', $theme->slug) }}" method="POST">
                            @csrf
                            <button type="submit">有効化</button>
                        </form>
                        <form action="{{ route('theme.delete', $theme->slug) }}" method="POST">
                            @csrf
                            <button type="submit" onclick="return confirm('本当に削除しますか？')">削除</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>

        <!-- Pagination links -->
        <div class="mt-6">
            {{ $themes->links('pagination::tailwind') }}
        </div>

@endsection
