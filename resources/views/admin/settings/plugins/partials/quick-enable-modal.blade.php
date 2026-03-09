{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

インストール直後のプラグイン有効化フォーム
バナーのボタンから2段階モーダルフローで呼び出される
--}}

<form action="{{ route('admin.settings.plugins.enable', $card['id']) }}" method="POST" id="quickEnableForm">
    @csrf
</form>
