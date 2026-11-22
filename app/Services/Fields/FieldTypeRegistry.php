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

namespace App\Services\Fields;

use App\Contracts\PluginIntegration\FieldTypeInterface;
use App\DTO\Fields\FieldDefinition;
use App\Fields\Types\DateFieldType;
use App\Fields\Types\DateTimeFieldType;
use App\Fields\Types\NumberFieldType;
use App\Fields\Types\SelectFieldType;
use App\Fields\Types\TextareaFieldType;
use App\Fields\Types\TextFieldType;
use App\Fields\Types\ToggleFieldType;
use App\Fields\Types\UrlFieldType;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Every field type core and the enabled extensions offer, keyed by key()
 *
 * Core's types come first, then each extension's in the order the resolver
 * returns them. A key that is already taken keeps the type that took it: the
 * later one is left out, logged, and listed by leftOut(), so a clash is
 * visible rather than settled by load order in silence. An extension's key
 * has to start with its plugin slug and a dot; one that does not is left out
 * the same way.
 *
 * Bound as a singleton. The list is collected on first use.
 */
class FieldTypeRegistry
{
    /**
     * The types core ships, in the order they are listed
     *
     * @var list<class-string<FieldTypeInterface>>
     */
    public const CORE_TYPES = [
        TextFieldType::class,
        TextareaFieldType::class,
        NumberFieldType::class,
        ToggleFieldType::class,
        SelectFieldType::class,
        DateFieldType::class,
        DateTimeFieldType::class,
        UrlFieldType::class,
    ];

    /** @var array<string, FieldTypeInterface>|null */
    private ?array $types = null;

    /** @var list<array{key: string, kept: string, ignored: string, reason: string}> */
    private array $duplicates = [];

    public function __construct(
        private readonly PluginServiceResolver $resolver,
    ) {}

    /**
     * @return array<string, FieldTypeInterface>
     */
    public function all(): array
    {
        return $this->types ??= $this->collect();
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    public function find(string $key): ?FieldTypeInterface
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * @throws InvalidArgumentException when no type has the key
     */
    public function get(string $key): FieldTypeInterface
    {
        return $this->find($key)
            ?? throw new InvalidArgumentException("Unknown field type [{$key}].");
    }

    /**
     * The types that were left out, and why
     *
     * `reason` is `duplicate` (the key was already taken by `kept`) or
     * `unprefixed` (an extension's key does not start with its slug; `kept`
     * is then empty).
     *
     * @return list<array{key: string, kept: string, ignored: string, reason: string}>
     */
    public function leftOut(): array
    {
        $this->all();

        return $this->duplicates;
    }

    /**
     * The rules for one field: `required` or `nullable` from the definition,
     * then the type's own
     *
     * @return array<int, mixed>
     */
    public function rules(FieldDefinition $field): array
    {
        return [
            $field->required ? 'required' : 'nullable',
            ...$this->get($field->type)->rules($field),
        ];
    }

    /**
     * The rules for a set of fields, keyed `<prefix>.<key>` for a form whose
     * inputs are named `<prefix>[<key>]`
     *
     * @param  iterable<FieldDefinition>  $fields
     * @return array<string, array<int, mixed>>
     */
    public function rulesFor(iterable $fields, string $prefix = 'fields'): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $name = $prefix === '' ? $field->key : $prefix.'.'.$field->key;
            $rules[$name] = $this->rules($field);
        }

        return $rules;
    }

    /**
     * Turn validated input into stored values, keyed by field key
     *
     * A field whose input is missing or empty is stored as null.
     *
     * @param  iterable<FieldDefinition>  $fields
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function storeInput(iterable $fields, array $input): array
    {
        $stored = [];

        foreach ($fields as $field) {
            $type = $this->get($field->type);
            $raw = $input[$field->key] ?? null;
            $value = $raw === null || $raw === '' ? null : $type->fromInput($raw, $field);
            $stored[$field->key] = $value === null ? null : $type->toStorage($value, $field);
        }

        return $stored;
    }

    /**
     * Turn stored values into the API's `fields`, keyed by field key
     *
     * A field whose type is no longer offered (its extension was removed) is
     * left out rather than guessed at.
     *
     * @param  iterable<FieldDefinition>  $fields
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public function apiValues(iterable $fields, array $stored): array
    {
        $values = [];

        foreach ($fields as $field) {
            $type = $this->find($field->type);

            if ($type === null) {
                continue;
            }

            $raw = $stored[$field->key] ?? null;
            $value = $raw === null ? null : $type->fromStorage($raw, $field);
            $values[$field->key] = $value === null ? null : $type->toApi($value, $field);
        }

        return $values;
    }

    /**
     * Forget the collected list, so the next call collects it again
     */
    public function refresh(): void
    {
        $this->types = null;
        $this->duplicates = [];
    }

    /**
     * @return array<string, FieldTypeInterface>
     */
    private function collect(): array
    {
        $types = [];
        $this->duplicates = [];

        foreach (self::CORE_TYPES as $class) {
            $this->add($types, app($class), true);
        }

        foreach ($this->resolver->resolveAll(FieldTypeInterface::class) as $result) {
            if ($result->isResolved() && $result->instance instanceof FieldTypeInterface) {
                $this->add($types, $result->instance, false);
            }
        }

        return $types;
    }

    /**
     * @param  array<string, FieldTypeInterface>  $types
     */
    private function add(array &$types, FieldTypeInterface $type, bool $core): void
    {
        $key = $type->key();

        if (! $core && ! str_starts_with($key, $type->getPluginSlug().'.')) {
            $this->leaveOut($key, '', $type, 'unprefixed');

            return;
        }

        if (isset($types[$key])) {
            $this->leaveOut($key, $types[$key]::class, $type, 'duplicate');

            return;
        }

        $types[$key] = $type;
    }

    private function leaveOut(string $key, string $kept, FieldTypeInterface $type, string $reason): void
    {
        $this->duplicates[] = [
            'key' => $key,
            'kept' => $kept,
            'ignored' => $type::class,
            'reason' => $reason,
        ];

        Log::warning('Field type left out', [
            'key' => $key,
            'reason' => $reason,
            'plugin' => $type->getPluginSlug(),
            'class' => $type::class,
            'kept' => $kept,
        ]);
    }
}
