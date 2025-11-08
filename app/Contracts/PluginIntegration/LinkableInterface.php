<?php

namespace App\Contracts\PluginIntegration;

/**
 * リンク可能なコンテンツの最小契約
 * 
 * プラグイン間連携の基盤として使用します。
 * メニュー、検索、タグ付けなど、複数のプラグインで
 * コンテンツを参照する際の共通インターフェースです。
 * 
 * @package App\Contracts\PluginIntegration
 */
interface LinkableInterface
{
    /**
     * コンテンツの一意なID（ULID/UUID）を取得
     * 
     * @return string
     */
    public function getId(): string;

    /**
     * コンテンツのタイトルを取得
     * 
     * @return string
     */
    public function getTitle(): string;

    /**
     * コンテンツのURLを取得
     * 
     * @return string
     */
    public function getUrl(): string;

    /**
     * コンテンツのタイプを取得
     * 
     * 例: 'post', 'page', 'media', 'product', 'inquiry'
     * 
     * @return string
     */
    public function getType(): string;

    /**
     * コンテンツのソース（提供元）を取得
     * 
     * - コアの場合: 'core'
     * - プラグインの場合: プラグインスラッグ（例: 'dixlase-blog'）
     * 
     * @return string
     */
    public function getSource(): string;

    /**
     * コンテンツのソーステーブル名を取得（オプション）
     * 
     * デバッグやデータ整合性チェックに使用
     * 
     * @return string|null
     */
    public function getSourceTable(): ?string;
}
