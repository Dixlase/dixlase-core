# Field Types

## Overview

A field type is a kind of input that content can carry: text, a number, a date. Each type
knows how to draw its admin input, which rules a submitted value must pass, how the value
is stored and what it looks like in the API.

Core does not decide where field values are stored or which content they belong to. The
extension that owns the content (a custom content type, a block) keeps a list of
`FieldDefinition`s and a map of stored values, and asks `FieldTypeRegistry` for the rest.

- **Contract**: `App\Contracts\PluginIntegration\FieldTypeInterface` (`@api`)
- **Definition**: `App\DTO\Fields\FieldDefinition` (`@api`)
- **Registry**: `App\Services\Fields\FieldTypeRegistry` (`@api`, singleton)
- **Component**: `<x-form-field>` (`@api`)

## 1. Describing fields

```php
use App\DTO\Fields\FieldDefinition;

$fields = [
    new FieldDefinition('price', 'number', __('Price'), ['min' => 0, 'integer' => true], required: true),
    new FieldDefinition('size', 'select', __('Size'), ['choices' => ['s' => __('Small'), 'l' => __('Large')]]),
    new FieldDefinition('event_date', 'date', __('Date'), help: __('Shown on the card')),
];

// Stored as JSON and read back
$json = json_encode($fields);
$fields = array_map(FieldDefinition::fromArray(...), json_decode($json, true));
```

| Property | Meaning |
| --- | --- |
| `key` | Input name and key in the API's `fields`. Lower-case letters, digits and `_`, starting with a letter, at most 64 characters |
| `type` | A field type key (`text`, `my-plugin.map`) |
| `label` | Translated label |
| `options` | Read by the type (table below) |
| `required` | Adds `required` instead of `nullable` to the rules |
| `help` | Translated help text, shown under the input |

## 2. Core types

| Key | Options | Stored and API value |
| --- | --- | --- |
| `text` | `min_length`, `max_length`, `placeholder` | string |
| `textarea` | `min_length`, `max_length`, `placeholder`, `rows` | string, line breaks kept |
| `number` | `min`, `max`, `step`, `integer` | int, or int/float without `integer` |
| `toggle` | — | bool |
| `select` | `choices` (value => label) | the choice's value; a value no longer in `choices` reads as null |
| `date` | `min`, `max` (`Y-m-d`) | `Y-m-d` |
| `datetime` | — | ISO 8601 in UTC; the editor types a wall clock in the site's display timezone |
| `url` | `max_length`, `placeholder` | string, `http` or `https` only |

An empty input is stored as null, and null is handed to the API as null.

## 3. Using the registry

```php
use App\Services\Fields\FieldTypeRegistry;

$registry = app(FieldTypeRegistry::class);

// Validation, for inputs named fields[<key>]
$request->validate($registry->rulesFor($fields));

// Submitted input -> values to store (JSON-serialisable)
$stored = $registry->storeInput($fields, $request->input('fields', []));

// Stored values -> the API's `fields`
$apiFields = $registry->apiValues($fields, $stored);
```

In a Blade form:

```blade
@foreach ($fields as $field)
    <x-form-field :field="$field" :value="$registry->get($field->type)->fromStorage($stored[$field->key] ?? null, $field)" />
@endforeach
```

`<x-form-field>` draws the label, the type's input, the help text and the errors. The
input is named `fields[<key>]` unless `name` is passed. A field whose type is no longer
offered draws nothing, and `apiValues()` leaves it out.

## 4. Adding a type from an extension

Implement `FieldTypeInterface` and tag the class:

```php
$this->app->tag([MapFieldType::class], PluginServiceResolver::CAPABILITY_TAG);
```

- The key must start with the plugin slug and a dot (`my-plugin.map`). A type whose key
  does not is left out.
- A key that is already taken keeps the first type. Core's types are collected first, so
  an extension cannot replace one.
- Both cases are logged as `Field type left out` and listed by `FieldTypeRegistry::leftOut()`.
- The stored value and the API value must survive `json_encode()`.
- `fromStorage()` must not throw for a value it does not recognise: a stored value may
  predate a change to the definition. Return null.

## 5. Block settings

`BlockDescriptor::$configSchema` takes a list of `FieldDefinition`s, or the arrays
`FieldDefinition::toArray()` produces. `BlockDescriptor::configFields()` returns them as
definitions, skipping entries in any other shape, so block settings are drawn, validated
and stored by the same types.

## 6. Not yet available

Media and rich-text field types come with the media reference (#1102) and the block
document format (#1104).
