<div class="mb-4">
    @include('components.form.label', [
        'for' => 'last_name',
        'text' => '姓',
    ])
    @include('components.form.text', [
        'id' => 'last_name',
        'name' => 'last_name',
        'value' => old('last_name', $user->last_name ?? ''),
        'required' => true,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('last_name')
    ])
</div>

<div class="mb-4">
    @include('components.form.label', [
        'for' => 'first_name',
        'text' => '名',
    ])
    @include('components.form.text', [
        'id' => 'first_name',
        'name' => 'first_name',
        'value' => old('first_name', $user->first_name ?? ''),
        'required' => true,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('first_name')
    ])
</div>

<div class="mb-4">
    @include('components.form.label', [
        'for' => 'email',
        'text' => 'メールアドレス',
    ])
    @include('components.form.text', [
        'type' => 'email',
        'id' => 'email',
        'name' => 'email',
        'value' => old('email', $user->email ?? ''),
        'required' => true,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('email')
    ])
</div>

<div class="mb-4">
    @include('components.form.label', [
        'for' => 'password',
        'text' => 'パスワード',
    ])
    @include('components.form.text', [
        'type' => 'password',
        'id' => 'password',
        'name' => 'password',
        'required' => true,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('password')
    ])
</div>
