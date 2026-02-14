@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# PHPUnit

- このアプリケーションは PHPUnit を使用する。全テストは PHPUnit クラスで記述すること。新しいテストの作成には `{{ $assist->artisanCommand('make:test --phpunit {name}') }}` を使用する
- "Pest" で書かれたテストを見つけたら PHPUnit に変換する
- テストを更新したら、その個別テストを実行する
- 機能に関連するテストが通ったら、テストスイート全体を実行するかユーザーに確認する
- テストは全てのハッピーパス、失敗パス、エッジケースをカバーすること
- 承認なしに tests ディレクトリからテストやテストファイルを削除しない。これらは一時ファイルではなくアプリケーションのコアである

## テストの実行
- 確定前に最小限のテストをフィルタ指定で実行する
- 全テスト実行: `{{ $assist->artisanCommand('test --compact') }}`
- ファイル内の全テスト実行: `{{ $assist->artisanCommand('test --compact tests/Feature/ExampleTest.php') }}`
- 特定テスト名でフィルタ: `{{ $assist->artisanCommand('test --compact --filter=testName') }}`（関連ファイル変更後に推奨）
