<!-- 削除ボタン -->
@include('components.form.button', [
    'type' => $type,
    'label' => $label,
    'theme' => $theme,
    'onclick' => "openModal('" . $id_confirmation . "')",

])

<!-- 削除モーダル -->
@include('components.form.modal', [
    'id' => $id_confirmation,
    'title' => $title,
    'message' => $message,
    'confirm_label' => $label,
    'cancel_label' => $cancel_label,
    'theme' => $theme
])
