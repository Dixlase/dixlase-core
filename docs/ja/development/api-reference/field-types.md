# フィールドの種類

## 概要

フィールドの種類は、コンテンツが持てる入力の種類です（テキスト、数値、日付など）。種類ごとに、
管理画面での入力欄の描き方、送られた値が満たすべきルール、値の保存の形、API での見え方を
知っています。

フィールドの値をどこに保存し、どのコンテンツに付けるかは、コアは決めません。コンテンツを
持つ拡張機能（カスタムのコンテンツタイプやブロック）が `FieldDefinition` の一覧と保存した値を
持ち、それ以外を `FieldTypeRegistry` に任せます。

- **Contract**：`App\Contracts\PluginIntegration\FieldTypeInterface`（`@api`）
- **定義**：`App\DTO\Fields\FieldDefinition`（`@api`）
- **レジストリ**：`App\Services\Fields\FieldTypeRegistry`（`@api`、シングルトン）
- **コンポーネント**：`<x-form-field>`（`@api`）

## 1. フィールドを定義する

```php
use App\DTO\Fields\FieldDefinition;

$fields = [
    new FieldDefinition('price', 'number', __('Price'), ['min' => 0, 'integer' => true], required: true),
    new FieldDefinition('size', 'select', __('Size'), ['choices' => ['s' => __('Small'), 'l' => __('Large')]]),
    new FieldDefinition('event_date', 'date', __('Date'), help: __('Shown on the card')),
];

// JSON で保存して読み戻す
$json = json_encode($fields);
$fields = array_map(FieldDefinition::fromArray(...), json_decode($json, true));
```

| プロパティ | 意味 |
| --- | --- |
| `key` | 入力欄の名前と、API の `fields` のキー。英小文字・数字・`_` で、英字で始まり、64 文字まで |
| `type` | フィールドの種類のキー（`text`、`my-plugin.map`） |
| `label` | 翻訳済みのラベル |
| `options` | 種類が読む設定（下の表） |
| `required` | ルールに `nullable` の代わりに `required` を入れる |
| `help` | 翻訳済みの説明。入力欄の下に出る |

## 2. コアの種類

| キー | 設定 | 保存と API の値 |
| --- | --- | --- |
| `text` | `min_length`、`max_length`、`placeholder` | 文字列 |
| `textarea` | `min_length`、`max_length`、`placeholder`、`rows` | 文字列。改行はそのまま |
| `number` | `min`、`max`、`step`、`integer` | int。`integer` がなければ int か float |
| `toggle` | — | bool |
| `select` | `choices`（値 => ラベル） | 選んだ値。`choices` からなくなった値は null として読む |
| `date` | `min`、`max`（`Y-m-d`） | `Y-m-d` |
| `datetime` | — | UTC の ISO 8601。入力はサイトの表示タイムゾーンの時刻 |
| `url` | `max_length`、`placeholder` | 文字列。`http` と `https` だけ |

空の入力は null で保存し、API にも null で渡します。

## 3. レジストリを使う

```php
use App\Services\Fields\FieldTypeRegistry;

$registry = app(FieldTypeRegistry::class);

// fields[<key>] という名前の入力欄の検証
$request->validate($registry->rulesFor($fields));

// 送られた入力 -> 保存する値（JSON にできる）
$stored = $registry->storeInput($fields, $request->input('fields', []));

// 保存した値 -> API の `fields`
$apiFields = $registry->apiValues($fields, $stored);
```

Blade のフォームでは次のように書きます。

```blade
@foreach ($fields as $field)
    <x-form-field :field="$field" :value="$registry->get($field->type)->fromStorage($stored[$field->key] ?? null, $field)" />
@endforeach
```

`<x-form-field>` は、ラベル、種類ごとの入力欄、説明、エラーを描きます。`name` を渡さなければ
入力欄の名前は `fields[<key>]` です。種類がもう提供されていないフィールドは何も描かず、
`apiValues()` からも外れます。

## 4. 拡張機能から種類を足す

`FieldTypeInterface` を実装し、クラスにタグを付けます。

```php
$this->app->tag([MapFieldType::class], PluginServiceResolver::CAPABILITY_TAG);
```

- キーはプラグインのスラッグとドットで始めます（`my-plugin.map`）。そうでない種類は外されます。
- すでに使われているキーは、先に登録された種類のままです。コアの種類を先に集めるので、
  拡張機能がコアの種類を置き換えることはできません。
- どちらの場合も `Field type left out` としてログに残り、`FieldTypeRegistry::leftOut()` に
  並びます。
- 保存する値と API の値は `json_encode()` できる形にします。
- `fromStorage()` は、知らない値で例外を投げてはいけません。定義を変える前に保存された値が
  来ることがあるので、null を返します。

## 5. ブロックの設定

`BlockDescriptor::$configSchema` には、`FieldDefinition` の一覧か、`FieldDefinition::toArray()`
が作る配列の一覧を渡します。`BlockDescriptor::configFields()` はそれを定義として返し、ほかの形の
要素は飛ばします。これで、ブロックの設定も同じ種類で描き、検証し、保存できます。

## 6. まだないもの

メディアとリッチテキストの種類は、メディアの参照（#1102）とブロックの文書の形式（#1104）と
一緒に入ります。
