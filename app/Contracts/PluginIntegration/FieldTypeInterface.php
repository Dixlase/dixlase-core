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

namespace App\Contracts\PluginIntegration;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\Fields\FieldDefinition;

/**
 * A kind of field that content can carry: text, a number, a date, a map.
 *
 * A field type knows how to draw its input in the admin, which rules a
 * submitted value must pass, how the value is stored and how it is handed to
 * the API. Where the value is stored, and which content it belongs to, is up
 * to the extension that owns the content; a field type never touches storage
 * itself.
 *
 * Values pass through four forms:
 *
 *   submitted input --fromInput()--> PHP value --toStorage()--> stored value
 *   stored value --fromStorage()--> PHP value --toApi()--> API value
 *
 * The stored value and the API value must survive json_encode(): a scalar,
 * null, or an array of those. Every method receives the FieldDefinition, so a
 * type reads its own options (`max_length`, `choices`, ...) from it.
 *
 * Core ships text, textarea, number, toggle, select, date, datetime and url.
 * An extension adds its own by tagging the class:
 *
 * ```php
 * $this->app->tag([MapFieldType::class], PluginServiceResolver::CAPABILITY_TAG);
 * ```
 *
 * An extension's key must start with its plugin slug and a dot
 * (`my-plugin.map`), so it can never take a core key or another extension's.
 * App\Services\Fields\FieldTypeRegistry collects the types; a key that is
 * registered twice keeps the first and is reported through leftOut().
 */
interface FieldTypeInterface extends PluginCapabilityInterface
{
    /**
     * The key a FieldDefinition names in its `type`, e.g. `text` or `my-plugin.map`
     */
    public function key(): string;

    /**
     * A translated, human-readable name for the type
     */
    public function label(): string;

    /**
     * The Blade view that draws the input
     *
     * The view receives `$field` (FieldDefinition), `$name` (the input name),
     * `$id`, `$value` (a PHP value, or the raw input flashed by old() after a
     * failed validation) and `$describedBy` (the id of the help text, or null).
     * The label, the help text and the errors are drawn around it by
     * <x-form-field>, so the view draws the input alone.
     */
    public function view(): string;

    /**
     * Laravel validation rules for a submitted value, without `required` /
     * `nullable`: FieldTypeRegistry::rules() adds those from the definition
     *
     * @return array<int, mixed>
     */
    public function rules(FieldDefinition $field): array;

    /**
     * Turn a submitted value that passed rules() into the PHP value
     */
    public function fromInput(mixed $input, FieldDefinition $field): mixed;

    /**
     * Turn the PHP value into the form that is stored
     */
    public function toStorage(mixed $value, FieldDefinition $field): mixed;

    /**
     * Turn a stored value back into the PHP value
     *
     * A stored value may predate a change to the definition, so this must not
     * throw for a value it does not recognise; return null instead.
     */
    public function fromStorage(mixed $stored, FieldDefinition $field): mixed;

    /**
     * The value written into an API resource's `fields`
     */
    public function toApi(mixed $value, FieldDefinition $field): mixed;
}
