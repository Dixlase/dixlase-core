# PHP

@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
@if($assist->shouldEnforceStrictTypes())
- `.php` ファイルの先頭で常に strict typing を宣言する: `declare(strict_types=1);`
@endif
- 制御構文では単一行でも常に波括弧を使用する

## コンストラクタ
- `__construct()` では PHP 8 のコンストラクタプロパティプロモーションを使用する
    - `public function __construct(public GitHub $github) { }`
- コンストラクタが private でない限り、パラメータゼロの空 `__construct()` は許可しない

## 型宣言
- メソッドと関数には常に明示的な戻り値型を宣言する
- メソッドパラメータには適切な PHP 型ヒントを使用する

<!-- 明示的な戻り値型とメソッドパラメータの例 -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enum
@if(empty($assist->enums()) || preg_match('/[A-Z]{3,8}/', $assist->enumContents()))
- Enum のキーは通常 TitleCase にする。例: `FavoritePerson`, `BestLake`, `Monthly`
@else
- Enum のキーはアプリケーション内の既存の Enum 規約に従う
@endif

## コメント
- インラインコメントよりPHPDocブロックを優先する。ロジックが非常に複雑な場合を除き、コード内にコメントを書かない

## PHPDoc ブロック
- 配列には適切な array shape 型定義を追加する
