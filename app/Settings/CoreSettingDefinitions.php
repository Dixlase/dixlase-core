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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

declare(strict_types=1);

namespace App\Settings;

use App\Enums\SettingScope;
use App\Services\Site\SettingDefinition;
use App\Services\Site\SettingDefinitionRegistry;

/**
 * Registers core (non-plugin) setting definitions.
 *
 * Each existing site_settings key is classified as one of:
 *   - Global       network-wide; one value across all sites
 *   - PerSite      site-specific; each site stores its own value
 *   - Overridable  global default + per-site override
 *
 * Plugins register their own definitions in their service providers.
 * Data migration for keys that change storage tier (e.g. Global keys
 * currently stored in site_settings) is handled by the SettingResolver
 * integration step, not here.
 */
class CoreSettingDefinitions
{
    public static function register(SettingDefinitionRegistry $registry): void
    {
        // ------------------------------------------------------------------
        // Network-wide (Global)
        // ------------------------------------------------------------------
        // Application name shown in the admin chrome. Site-facing display
        // names live on the Site model itself.
        $registry->register(new SettingDefinition(
            name: 'app_name',
            scope: SettingScope::Global,
            default: 'Dixlase',
            type: 'string',
        ));

        // Admin URL prefix (e.g. "admin"). Network-wide so admin sessions
        // resolve to the same admin entry point regardless of front host.
        $registry->register(new SettingDefinition(
            name: 'admin_url',
            scope: SettingScope::Global,
            default: 'admin',
            type: 'string',
        ));

        // Force HTTPS for the entire network.
        $registry->register(new SettingDefinition(
            name: 'force_ssl',
            scope: SettingScope::Global,
            default: false,
            type: 'bool',
        ));

        // Free-text notice rendered on the admin login screen (empty = hidden).
        $registry->register(new SettingDefinition(
            name: 'admin_login_notice',
            scope: SettingScope::Global,
            default: '',
            type: 'string',
        ));

        // Email address that receives system-level notifications.
        $registry->register(new SettingDefinition(
            name: 'system_admin_email',
            scope: SettingScope::Global,
            default: '',
            type: 'string',
        ));

        // Generic admin email (legacy alias used by the extension subsystem).
        $registry->register(new SettingDefinition(
            name: 'admin_email',
            scope: SettingScope::Global,
            default: '',
            type: 'string',
        ));

        // Admin UI complexity mode (e.g. simple / advanced) applied across
        // all sites. Personal preferences live elsewhere.
        $registry->register(new SettingDefinition(
            name: 'admin_mode',
            scope: SettingScope::Global,
            default: 'simple',
            type: 'string',
        ));

        // Per-menu visibility map for the simple admin mode. Stored as
        // a JSON object keyed by dot-notation menu key.
        $registry->register(new SettingDefinition(
            name: 'admin_mode_visibilities',
            scope: SettingScope::Global,
            default: null,
            type: 'array',
        ));

        // Admin UI theme (light / dark). Network-wide preference.
        $registry->register(new SettingDefinition(
            name: 'admin_theme',
            scope: SettingScope::Global,
            default: 'light',
            type: 'string',
        ));

        // Selected GUI editor plugin slug. Empty string = no GUI editor.
        // Read by EditorManager and AdminBaseEditorController; the value
        // refers to a plugin slug, so it is global like admin_theme.
        $registry->register(new SettingDefinition(
            name: 'preferred_gui_editor',
            scope: SettingScope::Global,
            default: '',
            type: 'string',
        ));

        // ------------------------------------------------------------------
        // Per-site
        // ------------------------------------------------------------------
        // Maintenance mode is per-site so individual sites can be taken
        // offline without affecting the rest of the network.
        $registry->register(new SettingDefinition(
            name: 'maintenance_mode',
            scope: SettingScope::PerSite,
            default: false,
            type: 'bool',
        ));
        $registry->register(new SettingDefinition(
            name: 'maintenance_message',
            scope: SettingScope::PerSite,
            default: '',
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'maintenance_auto_release',
            scope: SettingScope::PerSite,
            default: false,
            type: 'bool',
        ));
        $registry->register(new SettingDefinition(
            name: 'maintenance_start_at',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'maintenance_release_at',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));

        // Display locale and timezone for the site. These shadow the
        // sites.primary_locale / sites.timezone columns for legacy reads
        // and may be deprecated once all callers migrate to the Site model.
        $registry->register(new SettingDefinition(
            name: 'locale',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'timezone',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));

        // SMTP test state is captured per-site because each site may use
        // its own (overridable) outgoing mail configuration.
        $registry->register(new SettingDefinition(
            name: 'mail_connection_tested',
            scope: SettingScope::PerSite,
            default: false,
            type: 'bool',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_connection_test_date',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_send_tested',
            scope: SettingScope::PerSite,
            default: false,
            type: 'bool',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_send_test_date',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_receive_tested',
            scope: SettingScope::PerSite,
            default: false,
            type: 'bool',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_receive_test_date',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_verification_token',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));

        // Display name of the site (shown in front-end chrome, OGP, etc.).
        // Note: Site::name on the model holds the canonical site name; this
        // setting key remains for backward compatibility with legacy reads.
        $registry->register(new SettingDefinition(
            name: 'site_name',
            scope: SettingScope::PerSite,
            default: '',
            type: 'string',
        ));

        // Display timezone for date/time rendering (separate from the
        // canonical sites.timezone column for legacy callers).
        $registry->register(new SettingDefinition(
            name: 'display_timezone',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));

        // Per-site OGP and SEO defaults.
        $registry->register(new SettingDefinition(
            name: 'default_ogp_image_id',
            scope: SettingScope::PerSite,
            default: null,
            type: 'int',
        ));
        $registry->register(new SettingDefinition(
            name: 'site_description',
            scope: SettingScope::PerSite,
            default: '',
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'site_keywords',
            scope: SettingScope::PerSite,
            default: '',
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'twitter_card_type',
            scope: SettingScope::PerSite,
            default: 'summary_large_image',
            type: 'string',
        ));

        // ------------------------------------------------------------------
        // Overridable (global default + per-site override)
        // ------------------------------------------------------------------
        // Outgoing mail. The network operator typically configures one SMTP
        // relay; individual sites override only when they need a different
        // sender or relay (e.g. compliance, branding).
        $registry->register(new SettingDefinition(
            name: 'mail_mailer',
            scope: SettingScope::Overridable,
            default: 'smtp',
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_host',
            scope: SettingScope::Overridable,
            default: '',
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_port',
            scope: SettingScope::Overridable,
            default: 587,
            type: 'int',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_username',
            scope: SettingScope::Overridable,
            default: '',
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_password',
            scope: SettingScope::Overridable,
            default: '',
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_encryption',
            scope: SettingScope::Overridable,
            default: 'tls',
            type: 'string',
        ));
        $registry->register(new SettingDefinition(
            name: 'mail_from_address',
            scope: SettingScope::Overridable,
            default: '',
            type: 'string',
        ));

        // Display name shown in the From: header. Pairs with mail_from_address.
        $registry->register(new SettingDefinition(
            name: 'mail_from_name',
            scope: SettingScope::Overridable,
            default: '',
            type: 'string',
        ));

        // Email address used for system-generated notifications (login
        // lockouts, email verifications, etc.). Site operators can override
        // the network default.
        $registry->register(new SettingDefinition(
            name: 'notification_email',
            scope: SettingScope::Overridable,
            default: '',
            type: 'string',
        ));

        // Number of revisions kept per content item (FrontPage, Pages, Legal,
        // etc.) before older non-protected entries are pruned. Read by
        // RevisionService::getRetentionCount(); plugins may override the
        // effective value through their own service (e.g. LegalRevisionService).
        $registry->register(new SettingDefinition(
            name: 'content.revision.retention_count',
            scope: SettingScope::Global,
            default: 50,
            type: 'int',
        ));
    }
}
