# リソースの形

## 概要

`App\DTO\Api\ApiResourceDTO`（`@api`）は、`ApiResourceProviderInterface` を実装した拡張機能が
コンテンツをコアに渡すための形です。このページでは各プロパティの意味を説明します。どの
プロバイダーも同じように埋め、ヘッドレスのフロントは 1 つの形だけを知ればよいようにします。

リソースのデータは 3 か所に入り、それぞれ役割が 1 つです。

| 場所 | 入れるもの |
| --- | --- |
| 名前のあるプロパティ | ページかどうかに関わらず、どのリソースも持つもの |
| `fields` | リソースの型のある値。フィールドのキーで引く |
| `meta` | コンテンツではない、プロバイダー独自のおまけ |

## 1. プロパティ

| PHP | JSON | 型 | 意味 |
| --- | --- | --- | --- |
| `id` | `id` | string | リソースの ID |
| `type` | `type` | string | リソースの種類（`page`、`post`、`event`） |
| `slug` | `slug` | string | スラッグ |
| `title` | `title` | string\|null | タイトル |
| `content` | `content` | string\|null | 本文。形式は `content_format` が示す |
| `contentFormat` | `content_format` | string\|null | `html`、`markdown`、`blocks` のどれか。指定しないときは null |
| `excerpt` | `excerpt` | string\|null | 抜粋 |
| `metaDescription` | `meta_description` | string\|null | メタディスクリプション |
| `status` | `status` | string | `published`、`draft`、`scheduled` |
| `publishedAt` | `published_at` | string\|null | ISO 8601 |
| `updatedAt` | `updated_at` | string\|null | ISO 8601 |
| `url` | `url` | string\|null | フロントの URL |
| `source` | `source` | string | 提供する拡張機能のスラッグ |
| `fields` | `fields` | object | 型のある値。フィールドのキーで引く。ないときは `{}` |
| `meta` | `meta` | object\|array | プロバイダーのおまけ |

`contentFormat` と `fields` はコンストラクタの最後にあり、省略できます。そのため、この 2 つが
入る前に書いた呼び出しもそのまま動きます。3 つ以外の形式は `InvalidArgumentException` で
拒否します。`ApiResourceDTO::CONTENT_FORMAT_*` の定数を使ってください。

## 2. `content_format`

| 値 | `content` の中身 | 受け取る側は |
| --- | --- | --- |
| `html` | 描画済みの HTML | そのまま出す |
| `markdown` | Markdown のソース | 描画する |
| `blocks` | JSON のブロックの文書（#1104） | ブロックごとに描画する |
| null | 指定なし（これまでのリソースはすべてこれ） | プロバイダーの説明を見る。推測しない |

## 3. `fields`

値は JSON のスカラー、リスト、オブジェクトです。保存したカスタムフィールドから
`FieldTypeRegistry::apiValues()` で作ると、値はそれぞれフィールドの種類が決めた形になります
（[field-types.md](field-types.md) を参照）。

```php
$fields = app(FieldTypeRegistry::class)->apiValues($definitions, $storedValues);

return new ApiResourceDTO(/* ... */, contentFormat: ApiResourceDTO::CONTENT_FORMAT_HTML, fields: $fields);
```

2 種類の参照は形が決まっています。フロントはフィールドを知らなくても見分けられます。

| 参照先 | オブジェクト | 例 |
| --- | --- | --- |
| メディア | 少なくとも `id` と `url` | `{"id": 12, "url": "https://example.com/media/hero.jpg"}` |
| 別のリソース | 少なくとも `type` と `id` | `{"type": "place", "id": "7"}` |

参照はキーを足してもかまいません（`alt`、`width`、`slug`、`url`）。減らしてはいけません。

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

`meta` には、プロバイダーが渡したいもののうちコンテンツではないもの（カウンター、
キャッシュのヒント、プロバイダー独自の ID）を入れます。コンテンツは入れないでください。
フロントが表示する値は `fields` に入れます。そうすれば、その形は 1 つのプロバイダーの
癖ではなく、フィールドの種類が決めます。
