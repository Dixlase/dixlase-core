@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# テストの必須化

- 全ての変更はプログラム的にテストすること。新しいテストを作成するか既存テストを更新し、該当テストが通ることを確認する
- コード品質と速度を確保するため、必要最小限のテストを実行する。`{{ $assist->artisanCommand('test --compact') }}` にファイル名やフィルタを指定して使用する
