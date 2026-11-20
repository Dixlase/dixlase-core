<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Contracts;

/**
 * Finds the models whose translations contain the words searched for.
 *
 * TranslationResolver reads and writes the translations of one model at a
 * time. This contract answers the other question a search needs: which
 * models have a translation that contains these words. Without it, an
 * extension can search only its own columns, so a visitor searching in a
 * translated language finds nothing.
 *
 * The translation provider binds an implementation; core ships none. Do not
 * resolve the contract directly — call LocaleHelper::findTranslatedKeys(),
 * which returns an empty list when no provider is installed, so the caller
 * falls back to its own columns with no extra check.
 *
 * Matching rules for implementations:
 *
 * - A model matches when **every** word appears in **at least one** of the
 *   fields, in the given locale. The words may sit in different fields.
 * - Each word is a plain substring. The implementation escapes the LIKE
 *   wildcards (`%`, `_` and its escape character) itself; callers pass the
 *   words as the visitor typed them.
 * - An empty word list, an empty field list, or a model class with no
 *   translations returns an empty list.
 *
 * A caller that wants a word to match either its own column or a
 * translation calls the helper once per word, so one word can match the
 * source text and another only a translation:
 *
 * ```php
 * foreach ($words as $word) {
 *     $keys = LocaleHelper::findTranslatedKeys(Post::class, $fields, $locale, [$word]);
 *     $pattern = $this->likePattern($word); // escaped the way your search already does
 *
 *     $query->where(function ($q) use ($fields, $pattern, $keys) {
 *         foreach ($fields as $field) {
 *             $q->orWhere($field, 'like', $pattern);
 *         }
 *         $q->orWhereIn('id', $keys);
 *     });
 * }
 * ```
 */
interface TranslationSearch
{
    /**
     * The primary keys of the models of the class whose translations match.
     *
     * @param  class-string  $modelClass  the translatable model class
     * @param  list<string>  $fields  the translated fields to search
     * @param  string  $locale  the locale whose translations are searched
     * @param  list<string>  $words  the words that must all appear
     * @return list<int|string>
     */
    public function findKeys(string $modelClass, array $fields, string $locale, array $words): array;
}
