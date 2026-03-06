<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace App\Contracts\PluginIntegration;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\PluginIntegration\DashboardNotificationDTO;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * ダッシュボード通知を提供するプラグインの契約
 *
 * プラグインがダッシュボードに警告・推奨・情報通知を
 * 表示するためのインターフェースです。
 * PluginCapabilityInterface を継承し、PluginServiceResolver 経由で自動発見されます。
 */
interface DashboardNotificationProviderInterface extends PluginCapabilityInterface
{
    /**
     * ダッシュボード通知の一覧を取得
     *
     * @return DashboardNotificationDTO[]
     */
    public function getNotifications(): array;
}
