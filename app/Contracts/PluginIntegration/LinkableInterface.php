<?php

namespace App\Contracts\PluginIntegration;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * リンク可能なコンテンツの最小契約
 *
 * プラグイン間連携の基盤として使用します。
 * メニュー、検索、タグ付けなど、複数のプラグインで
 * コンテンツを参照する際の共通インターフェースです。
 */
interface LinkableInterface
{
    /**
     * コンテンツの一意なID（ULID/UUID）を取得
     */
    public function getId(): string;

    /**
     * コンテンツのタイトルを取得
     */
    public function getTitle(): string;

    /**
     * コンテンツのURLを取得
     */
    public function getUrl(): string;

    /**
     * コンテンツのタイプを取得
     *
     * 例: 'post', 'page', 'media', 'product', 'inquiry'
     */
    public function getType(): string;

    /**
     * コンテンツのソース（提供元）を取得
     *
     * - コアの場合: 'core'
     * - プラグインの場合: プラグインスラッグ（例: 'dixlase-blog'）
     */
    public function getSource(): string;

    /**
     * コンテンツのソーステーブル名を取得（オプション）
     *
     * デバッグやデータ整合性チェックに使用
     */
    public function getSourceTable(): ?string;
}
