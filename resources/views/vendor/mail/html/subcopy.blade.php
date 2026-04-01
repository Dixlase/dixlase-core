<table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
@php
    // subcopyの内容を取得
    $subcopyContent = trim((string) $slot);
    
    // "If you're having trouble clicking the" で始まる場合は多言語化
    if (preg_match('/^If you\'re having trouble clicking the "(.+?)" button/', $subcopyContent, $matches)) {
        $buttonText = $matches[1];
        // subcopyの残りの部分（URL）を取得
        preg_match('/into your web browser:\s*(.+)$/s', $subcopyContent, $urlMatches);
        $url = isset($urlMatches[1]) ? trim(strip_tags($urlMatches[1])) : '';
        
        // 翻訳キーを取得（Mailable側から $translationKey 変数として渡される想定）
        // 渡されていない場合はメンバー用のみフォールバック（後方互換性）
        if (!isset($translationKey)) {
            $translationKey = 'mail.login_notification.action_subcopy';
        }
        
        // 多言語化されたメッセージを表示
        echo '<p style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative; line-height: 1.5em; margin-top: 0; text-align: left; font-size: 14px;">';
        echo __($translationKey, ['button_text' => $buttonText]);
        echo ' <span class="break-all" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative; word-break: break-all;"><a href="' . $url . '" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative; color: #3869d4; word-break: break-all;">' . $url . '</a></span>';
        echo '</p>';
    } else {
        // 通常のsubcopy表示
        echo Illuminate\Mail\Markdown::parse($slot);
    }
@endphp
</td>
</tr>
</table>
