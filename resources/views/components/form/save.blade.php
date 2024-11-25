@props([
    'type' => 'button',      // ボタンのタイプ (button, submit, reset)
    'class' => '',                          // カスタムクラス
    'label' => '保存',                    // ボタンのテキスト
    'id' => 'confirmationModal',            // モーダルのID
    'title' => '保存の確認',                  // モーダルのタイトル
    'message' => 'この内容で保存しますか？',    // モーダルのメッセージ
    'cancelText' => '戻る',                  // キャンセルボタンのテキスト
    'theme' => 'light',                     // テーマ
])
<!-- 保存ボタン -->
@include('components.form.button', [
    'type' => $type,
    'label' => $label,
    'theme' => $theme,
])

<!-- モーダル -->
@include('components.form.modal', [
    'id' => $id,
    'title' => $title,
    'message' => $message,
    'confirmText' => $label,
    'cancelText' => $cancelText,
    'theme' => $theme
])
