<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services;

/**
 * @internal For Core use only. Do not reference from plugins/themes
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

        return $m[0]; // Return the matched string as-is
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
