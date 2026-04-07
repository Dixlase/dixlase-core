<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Contracts\Plugin;

/**
 * メール送信機能を宣言するインターフェース
 *
 * メール送信機能を持つプラグインが実装します。
 * plugin.json の permissions.mail.send が true であることが前提です。
 *
 * PluginServiceResolver 経由で解決する際に、
 * 'mail.send' 権限が自動的にチェックされます。
 */
interface MailCapableInterface extends PluginCapabilityInterface
{
    /**
     * このインターフェースに必要な権限キー
     */
    public const REQUIRED_PERMISSION = 'mail.send';

    /**
     * メール送信をサポートしているか
     */
    public function supportsMailSending(): bool;

    /**
     * 一括送信をサポートしているか
     */
    public function supportsBulkMailSending(): bool;
}
