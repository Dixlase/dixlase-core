<?php

namespace App\Services;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class ShortcodeManager
{
    protected $app;

    protected $shortcodes = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function add($tag, $class)
    {
        $this->shortcodes[$tag] = $class;
    }

    public function parse($content)
    {
        if (empty($this->shortcodes) || ! is_string($content)) {
            return $content;
        }

        $pattern = $this->getRegex();

        return preg_replace_callback("/$pattern/s", [$this, 'doShortcodeTag'], $content);
    }

    protected function doShortcodeTag($m)
    {
        $tag = $m[2];
        $attr = $this->parseAttributes($m[3]);
        $content = $m[5] ?? null;

        if (isset($this->shortcodes[$tag])) {
            $shortcode = $this->app->make($this->shortcodes[$tag]);

            return $shortcode->render($attr, $content);
        }

        return $m[0]; // マッチした文字列をそのまま返す
    }

    protected function getRegex()
    {
        $tagnames = array_keys($this->shortcodes);
        $tagregexp = implode('|', array_map('preg_quote', $tagnames));

        return '\\[(\\[?)'
            ."($tagregexp)"
            .'\\b([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:([^\\[]*+(?:\\[(?!\\/\\2\\])[^\\[]*+)*+)\\[\\/\\2\\])?)(\\]?)';
    }

    protected function parseAttributes($text)
    {
        $atts = [];
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", ' ', $text);

        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (! empty($m[1])) {
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                } elseif (! empty($m[3])) {
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                } elseif (! empty($m[5])) {
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                } elseif (isset($m[7]) && strlen($m[7])) {
                    $atts[] = stripcslashes($m[7]);
                } elseif (isset($m[8])) {
                    $atts[] = stripcslashes($m[8]);
                }
            }
        }

        return $atts;
    }
}
