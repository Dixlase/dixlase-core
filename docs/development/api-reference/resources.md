# Resource Shape

## Overview

`App\DTO\Api\ApiResourceDTO` (`@api`) is how an extension implementing
`ApiResourceProviderInterface` hands a piece of content to core. This page describes what
each property means, so every provider fills it the same way and a headless front end has
one shape to learn.

A resource carries its data in three places, each with one job:

| Where | Holds |
| --- | --- |
| The named properties | What every resource has, page-like or not |
| `fields` | The resource's typed values, keyed by field key |
| `meta` | Provider-specific extras that are not part of the content |

## 1. Properties

| PHP | JSON | Type | Meaning |
| --- | --- | --- | --- |
| `id` | `id` | string | Resource ID |
| `type` | `type` | string | Resource type (`page`, `post`, `event`) |
| `slug` | `slug` | string | Slug |
| `title` | `title` | string\|null | Title |
| `content` | `content` | string\|null | Body, in the format `content_format` names |
| `contentFormat` | `content_format` | string\|null | `html`, `markdown` or `blocks`; null when unspecified |
| `excerpt` | `excerpt` | string\|null | Excerpt |
| `metaDescription` | `meta_description` | string\|null | Meta description |
| `status` | `status` | string | `published`, `draft` or `scheduled` |
| `publishedAt` | `published_at` | string\|null | ISO 8601 |
| `updatedAt` | `updated_at` | string\|null | ISO 8601 |
| `url` | `url` | string\|null | Front URL |
| `source` | `source` | string | Slug of the extension that provides it |
| `fields` | `fields` | object | Typed values, keyed by field key; `{}` when there are none |
| `meta` | `meta` | object\|array | Provider extras |

`contentFormat` and `fields` come last in the constructor and are optional, so a call
written before they existed keeps working. A format outside the three is refused with an
`InvalidArgumentException`; use the `ApiResourceDTO::CONTENT_FORMAT_*` constants.

## 2. `content_format`

| Value | `content` holds | A consumer should |
| --- | --- | --- |
| `html` | Rendered HTML | Print it |
| `markdown` | Markdown source | Render it |
| `blocks` | A block document, as JSON (#1104) | Render each block |
| null | Unspecified, as every resource was before | Read the provider's documentation; do not guess |

## 3. `fields`

Values are JSON scalars, lists or objects. Build them from stored custom fields with
`FieldTypeRegistry::apiValues()`, so each value takes the shape its field type defines
(see [field-types.md](field-types.md)):

```php
$fields = app(FieldTypeRegistry::class)->apiValues($definitions, $storedValues);

return new ApiResourceDTO(/* ... */, contentFormat: ApiResourceDTO::CONTENT_FORMAT_HTML, fields: $fields);
```

Two kinds of reference have a fixed shape, so a front end can recognise them without
knowing the field:

| Reference to | Object | Example |
| --- | --- | --- |
| A media item | at least `id` and `url` | `{"id": 12, "url": "https://example.com/media/hero.jpg"}` |
| Another resource | at least `type` and `id` | `{"type": "place", "id": "7"}` |

A reference may carry more keys (`alt`, `width`, `slug`, `url`), but never fewer.

```json
{
  "type": "event",
  "content": "<p>Doors open at six.</p>",
  "content_format": "html",
  "fields": {
    "starts_on": "2026-12-01",
    "hero": {"id": 12, "url": "https://example.com/media/hero.jpg"},
    "venue": {"type": "place", "id": "7"}
  },
  "meta": {"views": 3}
}
```

## 4. `meta`

`meta` is for what a provider wants to pass along that is not content: counters, cache
hints, its own identifiers. Do not put content there. A value a front end displays belongs
in `fields`, where its shape comes from a field type rather than from one provider's habit.
