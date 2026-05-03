<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

return [
    // Subject Lines
    'subject_installed' => '[:app_name] :type ":name" has been installed',
    'subject_uninstalled' => '[:app_name] :type ":name" has been uninstalled',
    'subject_enabled' => '[:app_name] :type ":name" has been enabled',
    'subject_disabled' => '[:app_name] :type ":name" has been disabled',
    'subject_unhealthy_warning' => '[:app_name Warning] :type requiring health attention has been operated',

    // Types
    'type_plugin' => 'Plugin',
    'type_theme' => 'Theme',

    // Body
    'greeting' => 'Dear System Administrator',
    'message_installed' => ':type ":name" has been installed.',
    'message_uninstalled' => ':type ":name" has been uninstalled.',
    'message_enabled' => ':type ":name" has been enabled.',
    'message_disabled' => ':type ":name" has been disabled.',
    'message_unhealthy_warning' => 'A :type with health status other than "Healthy" has been operated. Please review the details.',

    // Details
    'details_title' => 'Operation Details',
    'extension_name' => 'Extension Name',
    'extension_type' => 'Type',
    'operation' => 'Operation',
    'operation_installed' => 'Install',
    'operation_uninstalled' => 'Uninstall',
    'operation_enabled' => 'Enable',
    'operation_disabled' => 'Disable',
    'operated_by' => 'Operated By',
    'operated_at' => 'Operation Time',
    'health_status' => 'Health Status',
    'health_healthy' => 'Healthy',
    'health_warning' => 'Warning',
    'health_needs_attention' => 'Needs Attention',
    'health_not_verified' => 'Not Verified',
    'version' => 'Version',

    // Warning Messages
    'unhealthy_notice' => 'This extension has a health status of ":level". We recommend reviewing the features and permissions it uses.',

    // Footer
    'regards' => 'Best regards,',
    'auto_notification' => 'This notification is automatically sent based on security settings.',
];
