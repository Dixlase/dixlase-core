<?php

if (!function_exists('shortcode_parse')) {
    /**
     * ショートコードをパースして実行
     *
     * @param string $content パース対象のコンテンツ
     * @return string パース後のコンテンツ
     */
    function shortcode_parse($content)
    {
        if (!app()->bound('shortcode')) {
            return $content;
        }
        
        return app('shortcode')->parse($content);
    }
}
