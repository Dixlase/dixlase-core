<?php

namespace App\Contracts\PluginIntegration;

use App\DTO\PluginIntegration\LinkableDTO;

/**
 * リンク可能なコンテンツを提供するプラグインの契約
 * 
 * メニュープラグインなどが他のプラグインからコンテンツを
 * 取得するための共通インターフェースです。
 * 
 * @package App\Contracts\PluginIntegration
 */
interface LinkableProviderInterface
{
    /**
     * プロバイダーの識別子を取得
     * 
     * @return string 例: 'dixlase-pages', 'dixlase-blog'
     */
    public function getProviderKey(): string;

    /**
     * プロバイダーの表示名を取得
     * 
     * @return string 例: 'ページ', 'ブログ記事'
     */
    public function getProviderLabel(): string;

    /**
     * プロバイダーのアイコンクラスを取得（オプション）
     * 
     * @return string|null 例: 'fas fa-file-alt'
     */
    public function getProviderIcon(): ?string;

    /**
     * このプロバイダーが現在利用可能かどうか
     * 
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * 利用可能なコンテンツのリストを取得
     * 
     * @param int $limit 取得件数の上限（デフォルト: 100）
     * @return LinkableDTO[]
     */
    public function getAvailableItems(int $limit = 100): array;

    /**
     * 検索クエリに基づいてコンテンツを検索
     * 
     * @param string $query 検索クエリ
     * @param int $limit 取得件数の上限（デフォルト: 20）
     * @return LinkableDTO[]
     */
    public function searchItems(string $query, int $limit = 20): array;

    /**
     * 特定のIDからコンテンツを取得
     * 
     * @param string $id コンテンツID
     * @return LinkableDTO|null
     */
    public function getItemById(string $id): ?LinkableDTO;
}
