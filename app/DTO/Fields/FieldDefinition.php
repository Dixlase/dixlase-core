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

namespace App\DTO\Fields;

use InvalidArgumentException;
use JsonSerializable;

/**
 * One field described as data: which type it is, how it is labelled and
 * which options its type reads
 *
 * A set of definitions is what an extension stores for a content type's
 * custom fields, and what a block lists as its settings
 * (BlockDescriptor::$configSchema). The `key` becomes the input name and the
 * key in an API resource's `fields`, so it is restricted to lower-case
 * letters, digits and underscores, starting with a letter.
 *
 * Options are read by the type. The core types understand:
 *
 *   text, textarea, url  min_length, max_length, placeholder (textarea: rows)
 *   number               min, max, step, integer (bool)
 *   select               choices (value => label)
 *   date                 min, max (Y-m-d)
 */
final readonly class FieldDefinition implements JsonSerializable
{
    public const KEY_PATTERN = '/^[a-z][a-z0-9_]{0,63}$/';

    /**
     * @param  string  $key  Input name and API key
     * @param  string  $type  A FieldTypeInterface::key()
     * @param  string  $label  Translated label
     * @param  array<string, mixed>  $options  Read by the type
     * @param  string|null  $help  Translated help text, shown under the input
     */
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public array $options = [],
        public bool $required = false,
        public ?string $help = null,
    ) {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new InvalidArgumentException(
                "Field key [{$key}] must be lower-case letters, digits and underscores, starting with a letter."
            );
        }

        if ($type === '') {
            throw new InvalidArgumentException("Field [{$key}] has no type.");
        }
    }

    /**
     * Build a definition from the array toArray() produces
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        foreach (['key', 'type', 'label'] as $required) {
            if (! isset($data[$required]) || ! is_string($data[$required])) {
                throw new InvalidArgumentException("A field definition needs a string [{$required}].");
            }
        }

        $options = $data['options'] ?? [];
        $help = $data['help'] ?? null;

        return new self(
            key: $data['key'],
            type: $data['type'],
            label: $data['label'],
            options: is_array($options) ? $options : [],
            required: (bool) ($data['required'] ?? false),
            help: is_string($help) && $help !== '' ? $help : null,
        );
    }

    /**
     * Read one option, or the default when it is not set
     */
    public function option(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * @return array{key: string, type: string, label: string, options: array<string, mixed>, required: bool, help: string|null}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'options' => $this->options,
            'required' => $this->required,
            'help' => $this->help,
        ];
    }

    /**
     * @return array{key: string, type: string, label: string, options: array<string, mixed>, required: bool, help: string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
