<?php

namespace App\Contracts\PluginIntegration;

/**
 * プライバシーポリシープロバイダーの契約
 *
 * 法務プラグインなどがこのインターフェースを実装して
 * ServiceContainerに登録することで、他のプラグインが
 * プライバシーポリシー情報を取得できるようになります。
 */
interface PrivacyPolicyProviderInterface
{
    /**
     * プライバシーポリシーのURLを取得
     */
    public function getPrivacyPolicyUrl(): ?string;

    /**
     * プライバシーポリシーが有効かどうか
     */
    public function isPrivacyPolicyEnabled(): bool;

    /**
     * プライバシーポリシーのラベルテキストを取得
     */
    public function getPrivacyPolicyLabel(): string;
}
