<ul class="nav flex-column">
    @foreach (config('admin.nav') as $key => $item)
        <li class="nav-item">
            <a href="{{ route($item['route']) }}" class="nav-link">
                <i class="{{ $item['icon'] }}"></i>
                <span>{{ __($item['text']) }}</span>
            </a>

            @if (isset($item['master']))
                <ul class="nav flex-column ml-3">
                    <li class="nav-item">
                        <a href="{{ route($item['master']['route']) }}" class="nav-link">
                            <span>{{ __($item['text']) }}</span>
                        </a>
                    </li>
                </ul>
            @endif
        </li>
    @endforeach
</ul>
