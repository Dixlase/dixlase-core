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

return [
    'heading' => 'Plugin Management',
    'description' => 'Manage installed plugins, add new plugins, and enable or disable plugins.',
    'installed_heading' => 'Installed Plugins',
    'update_available' => 'v:version available',
    'update_failed_at' => 'Update failed at :date',
    'updates' => [
        'check' => 'Check Updates',
        'checking' => 'Checking...',
        'all_up_to_date' => 'All plugins are up to date.',
        'updates_found' => ':count update(s) available.',
        'no_update' => 'No update available for this plugin.',
        'update_button' => 'Update',
        'update_to_version' => 'Update to v:version',
        'update_success' => 'Plugin ":name" has been updated to v:version.',
        'update_failed' => 'Plugin update failed: :error',
        'update_all_button' => 'Update All',
        'update_all_confirm_title' => 'Update All Plugins',
        'update_all_confirm_message' => 'Update :count plugin(s) to their latest versions in one batch. Continue?',
        'update_all_running' => 'Updating plugins sequentially. This may take a moment.',
        'update_all_summary' => ':succeeded of :total succeeded, :failed failed',
    ],
    'uninstalled_heading' => 'Uninstalled Plugins',
    'systems' => [
        'text' => 'System',
        'cache' => 'Cache Management',
        'database' => 'Database Management',
        'logs' => 'System Logs',
        'info' => 'System Information',
    ],
    'table' => [
        'id' => 'ID',
        'name' => 'Plugin Name',
        'caption' => 'Installed Plugins List',
    ],
    'no_plugins' => 'No plugins are installed.',
    'no_plugins_description' => 'Add plugins to extend your site\'s functionality.',
    'add_plugin' => 'Add Plugin',
    'view_details' => 'Details',
    'uninstalled_description' => 'These plugins have files present but are not yet installed.',
    'buttons' => [],
    'uninstall' => [
        'confirm_title' => 'Uninstall Confirmation',
        'confirm_message' => 'Do you want to uninstall plugin [{name}]?',
        'remove_data_checkbox' => 'Delete database tables created during plugin installation.<br><br><span class="text-red-600 font-semibold">Warning! Deleting tables will lose data created by the plugin!</span>',
    ],
    'install' => [
        'confirm_title' => 'Install Confirmation',
        'confirm_message' => 'Do you want to install plugin [{name}]?',
    ],
    'enabled' => [
        'confirm_message' => 'Do you want to enable plugin [{name}]?',
        'success' => '{name} has been enabled',
        'failed' => 'Failed to enable {name}',
    ],
    'disabled' => [
        'confirm_title' => 'Disable Confirmation',
        'confirm_message' => 'Do you want to disable plugin [{name}]?',
        'confirm_warning' => 'Features provided by this plugin will be temporarily unavailable.',
    ],
    'delete' => [
        'confirm_title' => 'Delete Confirmation',
        'confirm_message' => 'Do you want to permanently delete plugin [{name}] files and folders? This action cannot be undone.',
    ],
    'audit' => [
        'invalid_slug' => 'Invalid plugin slug.',
        'completed' => 'Plugin scan completed.',
        'failed' => 'Plugin scan failed.',
        'audit_all_button' => 'Re-scan All Plugins',
        'audit_all_confirm_title' => 'Re-scan All Plugins',
        'audit_all_confirm_message' => 'Sequentially scan all installed and uninstalled plugins. This may take a moment.',
        'audit_all_summary' => 'Scanned :succeeded of :total plugin(s) (:failed failed).',
    ],

    // Enable Action (PluginEnableAction Enum)
    'enable_action' => [
        'allowed' => 'Activation Allowed',
        'warning' => 'Warning: Minor Issues Detected',
        'ack' => 'Confirmation Required: Important Issues Detected',
        'blocked' => 'Activation Blocked: Critical Issues Detected',
        'blocked_message' => 'This plugin cannot be activated due to critical health issues. Please resolve the issues and re-scan.',
    ],

    // Rescan
    'rescan' => [
        'files_changed' => 'Plugin files have changed since the last scan. Re-scanning...',
        'auto_triggered' => 'Automatic security scan triggered.',
    ],

    // Scan freshness badges
    'scan_status' => [
        'unscanned' => 'Not scanned',
        'expired' => 'Scan expired (last scanned :age days ago / max :max)',
        'files_changed' => 'Files changed — re-scan recommended',
    ],

    // Health Issue Descriptions
    'health_issue' => [
        'csp_inline_css_required' => 'Inline CSS required. May not work in strict mode.',
        'csp_external_resources' => 'External resources detected. Review for security.',
        'signature_unsigned' => 'No signature. Signing is recommended for distribution.',
    ],

    // Badge Labels (for card display)
    'badge_labels' => [
        'health' => 'Health',
        'health_full' => 'Health',
        'signature' => 'Sign',
        'permission' => 'Perm',
        'csp' => 'CSP',
        'preset' => 'Ext',
        'operation' => 'Status',
    ],

    // CSP Mode Badge Labels
    'csp_mode' => [
        'development' => 'Dev',
        'standard' => 'Standard',
        'strict' => 'Strict',
        'not_checked' => 'Not Checked',
    ],

    // Security Preset Compatibility Badge Labels
    'preset_badge' => [
        'development' => 'Dev',
        'balanced' => 'Balanced',
        'strict' => 'Strict',
        'custom' => 'Current',
        'not_verified' => 'Not Verified',
    ],

    // Health Status (PluginHealthStatus Enum)
    'health_status' => [
        'healthy' => 'Healthy',
        'healthy_description' => 'No discrepancies found in declared permissions, signature, or configuration.',
        'healthy_tooltip' => 'No discrepancies found in declared permissions, signature, or configuration.',
        'advisory' => 'Advisory',
        'advisory_description' => 'Minor issues found. Does not immediately affect operation, but review is recommended.',
        'advisory_tooltip' => 'Minor issues found. Does not immediately affect operation, but review is recommended.',
        'needs_attention' => 'Needs Attention',
        'needs_attention_description' => 'Important issues found. Please review before activation or operation.',
        'needs_attention_tooltip' => 'Important issues found. Please review before activation or operation.',
        'not_verified' => 'Not Verified',
        'not_verified_description' => 'Verification information is insufficient (not scanned, no permission definition, unsigned, etc.).',
        'not_verified_tooltip' => 'Verification information is insufficient (not scanned, no permission definition, unsigned, etc.).',
    ],

    // Trust Level (PluginTrustLevel Enum)
    'trust_level' => [
        'official' => 'Official',
        'official_description' => 'Distributed by Dixlase official.',
        'verified' => 'Verified',
        'verified_description' => 'Distributed by a verified publisher.',
        'partner' => 'Partner',
        'partner_description' => 'Distributed by a Dixlase partner.',
        'community' => 'Community',
        'community_description' => 'Distributed by an unverified publisher.',
        'local' => 'Local',
        'local_description' => 'Manual installation or local development.',
    ],

    // Verification Status (PluginVerificationStatus Enum)
    'verification' => [
        // Signature
        'signature_valid' => 'Signed',
        'signature_unsigned' => 'Unsigned',
        'signature_invalid' => 'Invalid',
        'signature_waived' => 'Waived',
        'signature_pending' => 'Pending',
        'signature_pending_verification' => 'Pending Verification',
        'signature_not_scanned' => 'Unverified',
        // Permission
        'permission_ok' => 'OK',
        'permission_undefined' => 'Undefined',
        'permission_mismatch' => 'Mismatch',
        'permission_not_scanned' => 'Unverified',
        // Scan
        'scan_not_performed' => 'Scan: Not Performed',
        'scan_outdated' => 'Scan: Outdated',
        'scan_completed' => 'Scan: Completed',
        // CSP
        'csp_ready' => 'CSP Ready',
        'csp_compatible' => 'CSP Compatible',
        'csp_inline_required' => 'CSP Not Ready',
        'csp_not_checked' => 'CSP Not Checked',
    ],

    // Operation Status (Traffic Light)
    'operation_status' => [
        'ok' => 'Fully Operational',
        'caution' => 'Caution',
        'blocked' => 'Blocked',
        'unknown' => 'Unverified',
    ],

    // Operation status — detail page section
    'operation_status_heading' => 'Operation Status',
    'operation_status_description' => [
        'ok' => 'This plugin runs without restrictions under the current security settings.',
        'caution' => 'This plugin runs, but has issues worth reviewing (unverified permissions, missing signature, and so on).',
        'blocked' => 'This plugin cannot run under the current security settings. Lower the security preset or CSP mode, or resolve the reported issues.',
        'unknown' => 'Operation status has not been determined yet. Run a scan to evaluate it.',
    ],

    // Simple Mode Display
    'simple' => [
        'health_safe' => 'Safe',
        'health_caution' => 'Caution',
        'health_problem' => 'Problem',
        'operation_usable' => 'Available',
        'operation_unusable' => 'Unavailable',
        'unknown' => 'Unknown',
    ],

    // Modal Messages
    'modal' => [
        'health_check_title' => 'Health Check Details',
        'plugin_info' => 'Plugin: :name (:slug)',
        'version_info' => 'Version: :version',
        'last_scan_info' => 'Last Scan: :date / Scanner: v:version',

        // Healthy
        'healthy_heading' => 'Healthy (No issues detected)',
        'healthy_body' => 'No discrepancies between declared permissions and detected usage.',
        'healthy_note' => 'This result is based on the current ruleset.',

        // Advisory
        'advisory_heading' => 'Advisory (Review Recommended)',
        'advisory_body' => ':count minor issues found. Does not prevent operation, but review is recommended for transparency.',
        'advisory_action_permission' => 'Update plugin.json permissions to match actual usage.',
        'advisory_action_signature' => 'Add signature for production distribution.',

        // Needs Attention
        'needs_attention_heading' => 'Needs Attention (Review before activation)',
        'needs_attention_body' => ':count important issues found. Activation may be restricted by current security settings.',
        'needs_attention_action_reinstall' => 'Re-download from the original source and reinstall, then rescan.',
        'needs_attention_action_document' => 'If intentional, explicitly declare permissions and document the design intent.',

        // Not Verified
        'not_verified_heading' => 'Not Verified (Insufficient verification information)',
        'not_verified_body' => 'This plugin lacks information required for verification.',
        'not_verified_action_scan' => 'Run a rescan.',
        'not_verified_action_permission' => 'Define permissions in plugin.json.',
        'not_verified_action_signature' => 'Add signature for production distribution.',

        // Issue Examples
        'issue_permission_undeclared' => 'Undeclared permission: :permission (detected: :file::line)',
        'issue_permission_unused' => 'Unused permission declared: :permission',
        'issue_signature_unsigned' => 'Unsigned: Allowed in development mode (signature recommended for production)',
        'issue_signature_invalid' => 'Signature mismatch: Possible tampering',
        'issue_dangerous_api' => 'Discouraged API usage detected: :api (:file::line)',

        // Recommended Actions
        'recommended_actions' => 'Recommended Actions',
    ],

    // Block Messages
    'block' => [
        'title' => 'This plugin cannot be activated with current security settings',
        'body' => 'Health check determined status as ":status".',
        'action' => 'Change security settings to allow this level, or resolve issues and rescan.',
        'button_details' => 'View Details',
        'button_security' => 'Security Settings',
        'button_cancel' => 'Cancel',

        // CSP Strict Mode
        'csp_strict_title' => 'Cannot activate in CSP Strict Mode',
        'csp_strict_body' => 'This plugin requires inline JavaScript and will not work in CSP strict mode.',
        'csp_strict_action' => 'Change CSP mode to "Standard" or "Development", or update the plugin to be CSP Ready.',
    ],

    // CSP Compliance
    'csp' => [
        'status_label' => 'CSP Compliance',
        'ready' => 'CSP Ready',
        'ready_tooltip' => 'This plugin is fully CSP compliant. Works in all CSP modes.',
        'inline_required_tooltip' => 'This plugin requires inline JavaScript. Will not work in CSP strict mode.',
        'compatible' => 'CSP Compatible',
        'compatible_tooltip' => 'This plugin works with nonce. Works in standard mode and above.',
        'inline_required' => 'CSP Not Ready',
        'inline_required_tooltip' => 'This plugin requires inline JavaScript. Will not work in CSP strict mode.',
        'not_checked' => 'Not Checked',
        'not_checked_tooltip' => 'CSP compliance has not been verified.',

        // CSP Violation Warning (shown even when CSP is disabled)
        'violation_detected' => 'CSP violations detected',
        'violation_count' => ':count violations',
        'violation_note_disabled' => 'CSP is currently disabled, but issues may occur if enabled.',
        'violation_note_dev' => 'In development mode, violations are logged but not blocked.',
        'violation_note_standard' => 'In standard mode, some features may not work.',
        'violation_note_strict' => 'In strict mode, this plugin cannot be activated.',
    ],

    // Controller Messages
    'messages' => [
        'install_success' => 'Plugin has been installed successfully.',
        'install_success_no_plugin' => 'Plugin has been installed successfully.',
        'asset_build_failed' => 'The plugin was installed, but building its screen files (:command) failed, so its pages may be missing JavaScript and CSS. The npm output is in the log. To try again, run: php artisan dls:plugin:install :directory --build',
        'enable_here' => 'click here',
        'enable_cta' => 'Enable ":name"',
        'download_complete_cta' => 'Install ":name"',
        'install_failed' => 'Plugin installation failed: :error',
        'install_directory_not_found' => 'Plugin directory not found.',
        'uninstall_success' => 'Plugin has been uninstalled',
        'uninstall_failed' => 'Plugin uninstall failed: :error',
        'uninstall_must_disable_first' => 'Cannot uninstall an enabled plugin. Please disable it first.',
        'disable_success' => 'Plugin has been disabled',
        'disable_failed' => 'An error occurred while disabling plugin: :error',
        'delete_success' => 'Plugin has been deleted successfully.',
        'delete_failed' => 'Plugin deletion failed: :error',
        'delete_uninstall_failed' => 'Plugin uninstall failed.',
        'delete_file_failed' => 'Plugin file deletion failed.',
        'no_plugin_name' => 'No plugin name',
    ],

    'capabilities' => [
        'title' => 'Provided Capabilities',
        'description' => 'Features this plugin declares it provides. Core and other plugins use this declaration to detect capabilities.',
    ],

    'permissions' => [
        'health_status' => 'Health Status',
        'health_healthy' => 'Healthy',
        'health_warning' => 'Warning',
        'health_needs_attention' => 'Needs Attention',
        'health_not_verified' => 'Not Verified',
        'unknown' => 'Undefined',
        'unknown_warning' => 'Permission information is not defined. It is unknown what operations this plugin performs. Please confirm it was obtained from a trusted source.',
        'audit_mismatch_title' => 'Permission Mismatch',
        'audit_mismatch_warning' => 'Permissions declared in plugin.json do not match actual code.',
        'audit_undeclared_usage' => 'Undeclared Feature Usage',
        'audit_unused_declaration' => 'Unused Permission Declaration',
        'install_warning_title' => 'Pre-Installation Confirmation',
        'install_warning_notice' => 'This plugin has the following items to confirm:',
        'install_warning_undefined' => 'Permission information is undefined',
        'install_warning_unsigned' => 'Not signed',
        'install_warning_mismatch' => 'Permission declaration and code do not match',
        'install_warning_confirm' => 'Do you want to install understanding the above?',
        'install_warning_risk' => 'This plugin has the following notes:',
        'risk_medium' => 'Medium health risk',
        'risk_high' => 'High health risk',
        'enable_warning_title' => 'Pre-Activation Confirmation',
        'enable_warning_message' => 'Plugin ":name" has the following notes:',
        'enable_warning_confirm' => 'Do you want to enable understanding the above?',
        'enable_confirm_simple' => 'Do you want to enable plugin ":name"?',
        'enable_warning_invalid_signature' => 'Invalid signature (possible tampering)',
        'enable_warning_signature_waived' => 'Signature check waived by operator',
        'enable_warning_needs_attention' => 'Uses permissions that need attention',
        'enable_warning_high_risk' => 'Has high health risk',
        'warning_not_scanned' => 'Scan has not been run',
        'scan_recommendation' => 'We recommend running a scan before proceeding.',
        'audit_button' => 'Scan',
        'audit_button_rescan' => 'Rescan',
        'audit_scanning' => 'Scanning...',
        'audit_scanning_title' => 'Scanning Plugin',
        'audit_scanning_description' => 'Running security scan.<br>Please wait.',
        'audit_not_scanned' => 'Not Scanned',
        'audit_last_scanned' => 'Last Scanned',
        'audit_result_title' => 'Scan Results',
        'audit_no_issues' => 'No issues detected',
        'audit_stats' => 'Check Items',
        'audit_matches' => 'Matches',
        'audit_mismatches' => 'Mismatches',
        'audit_mismatch_badge' => 'Mismatch',
        'permission_consistency_title' => 'Permission Consistency',
        'total_risk_score' => 'Total Risk Score',
        'no_permissions' => 'Permission information is not defined',
        'details_title' => 'Plugin Details',
        'no_special_permissions' => 'No special permissions',
        'no_permissions_defined' => 'Permission information is not defined. This plugin\'s permissions are unknown.',
        'signature_status' => 'Signature Status',
        'signature_official' => 'Official',
        'signature_verified' => 'Verified',
        'signature_partner' => 'Partner',
        'signature_signed' => 'Signed',
        'signature_valid' => 'Signed',
        'signature_invalid' => 'Invalid Signature',
        'signature_unsigned' => 'Unsigned',
        'signature_pending_verification' => 'Pending Verification',
        'signature_unknown_key' => 'Unknown Signing Key',
        'signature_expired' => 'Key Expired',
        'signature_error' => 'Verification Error',
        'signature_invalid_warning' => '⚠️ This plugin\'s signature is invalid. It may have been tampered with.',
        'signature_unsigned_info' => 'This plugin is not signed. Please confirm it was obtained from a trusted source.',
        'signature_pending_verification_info' => 'This plugin has a signature, but verification against the public key server is not yet complete.',
        'signature_unknown_key_info' => 'The signing key for this plugin is not registered as a trusted key. Please verify the source.',
        'signature_expired_info' => 'The signing key used for this plugin has expired.',
        'signature_error_info' => 'An error occurred during signature verification.',
        'signed_by' => 'Signed By',
        'permission_info' => 'Permission Information',
        'database_owned_tables_label' => 'Created Tables',
        'database_owned_tables_description' => 'Tables this extension creates via its migrations.',
        'database_owned_tables_dynamic' => ':count dynamic table name(s) (cannot be statically resolved)',
        'database_owned_tables_empty' => 'This extension does not create any tables.',
        'database_owned_tables_source_declared' => 'Declared in plugin.json',
        'database_owned_tables_source_detected' => 'Auto-detected from migrations',
        'database_writes_to_other_label' => 'Writes to Other Extensions\' Tables',
        'database_writes_to_other_target' => 'Target plugin: :slug',
        'category_database' => 'Database',
        'category_storage' => 'Storage',
        'category_settings' => 'Settings',
        'category_members' => 'Members',
        'category_mail' => 'Mail',
        'category_content' => 'Content',
        'category_system' => 'System',
        'category_dangerous_api' => 'Dangerous API',
        'category_csp' => 'CSP',
        'category_migrations' => 'Migrations',
        'perm_stock_migrator' => 'Registered With Stock Migrator',
        'perm_own_tables' => 'Own Tables',
        'perm_core_tables_read' => 'Core Tables (Read)',
        'perm_core_tables_write' => 'Core Tables (Write)',
        'perm_own_directory' => 'Own Directory',
        'perm_public_uploads' => 'Public Uploads',
        'perm_temp_files' => 'Temp Files',
        'perm_read_core' => 'Read Core Settings',
        'perm_write_own' => 'Write Own Settings',
        'perm_read' => 'Read',
        'perm_write' => 'Write',
        'perm_create' => 'Create',
        'perm_delete' => 'Delete',
        'perm_send' => 'Send',
        'perm_bulk_send' => 'Bulk Send',
        'perm_read_other_plugins' => 'Read Other Plugins',
        'perm_write_other_plugins' => 'Write Other Plugins',
        'perm_register_shortcodes' => 'Shortcodes',
        'perm_register_middleware' => 'Middleware',
        'perm_register_commands' => 'Commands',
        'perm_register_blade_directives' => 'Blade Directives',
        'perm_modify_routes' => 'Modify Routes',
        'perm_exec' => 'Execute Commands',
        'perm_env_access' => 'Environment Variables',
        'perm_npm_lifecycle_scripts' => 'npm Install Scripts',
        'perm_file_write' => 'File Write',
        'perm_network' => 'External Network',
        'perm_external_resources' => 'External Resources',
        'total_evaluation' => 'Total Evaluation',
        'health_score_display' => 'Score: :score/100',
        'signature_deduction' => '(Deduction: -:points)',
        'health_issue_signature_unsigned' => 'Signature: Unsigned',
        'health_issue_signature_invalid' => 'Signature: Invalid',
        'health_issue_missing_author_id' => 'Metadata: author_id missing',
        'health_issue_missing_authority_key_id' => 'Metadata: authority_key_id missing',
        'health_issue_permission_undefined' => 'Permissions: Not Defined',
        'health_issue_permission_undeclared_minor' => 'Permission: Undeclared Usage (Minor)',
        'health_issue_permission_undeclared_major' => 'Permission: Undeclared Usage (Major)',
        'health_issue_permission_unused' => 'Permission: Unused Declaration',
        'health_issue_csp_inline_css_required' => 'CSP: Inline CSS Required',
        'health_issue_csp_inline_js_required' => 'CSP: Inline JS Required',
        'health_issue_csp_violation_strict' => 'CSP: Violation (Strict Mode)',
        'health_issue_csp_violation_standard' => 'CSP: Violation (Standard Mode)',
        'health_issue_dangerous_api_exec' => 'Dangerous API Detected',
        'health_issue_risk_public_uploads_own_dir' => 'Storage: Public uploads in own directory',
        'health_issue_risk_public_uploads_no_own_dir' => 'Storage: Direct public directory upload',
        'health_issue_risk_members_delete' => 'Permission: Member deletion',
        'health_issue_risk_mail_bulk_send' => 'Permission: Bulk email send',
        'health_issue_scan_not_performed' => 'Scan: Not Performed',
        'health_issue_scan_outdated' => 'Scan: Outdated',
        'health_status_healthy' => 'Healthy',
        'health_status_advisory' => 'Advisory',
        'health_status_needs_attention' => 'Needs Attention',
        'health_status_not_verified' => 'Not Verified',
        'signature_section_label' => 'Signature',
        'attention_reasons_title' => 'Reasons for Attention',
        'attention_reason_members_write' => 'Uses member information write permission',
        'attention_reason_members_create' => 'Uses new member creation permission',
        'attention_reason_members_delete' => 'Uses member deletion permission',
        'attention_reason_mail_bulk_send' => 'Uses bulk email send permission',
        'attention_reason_storage_public_uploads' => 'Uses public directory upload permission',
        'attention_reason_storage_public_uploads_own_dir' => 'Uses public uploads within own directory',
        'attention_reason_storage_public_uploads_no_own_dir' => 'Uploads directly to public directory',
        'attention_reason_content_write_other_plugins' => 'Uses other plugin write permission',
        'attention_reason_mail_send' => 'Uses email send permission',
        'attention_reason_settings_read_core' => 'Uses core settings read permission',
        'attention_reason_system_register_middleware' => 'Uses middleware registration permission',
        'attention_reason_database_core_tables_read' => 'Uses core table read permission',
        'attention_reason_database_core_tables_write' => 'Uses core table write permission',
        'attention_reason_undeclared_usage' => 'Uses undeclared permissions',
        'attention_reason_mismatch_undeclared_usage' => 'Undeclared permission usage detected (:count occurrences)',
        'attention_reason_system_modify_routes' => 'Uses route modification permission',
        'csp_status' => 'CSP Compliance',
        'csp_compliant' => 'Compliant',
        'csp_not_compliant' => 'Not Compliant',
        'csp_issues_found' => ':count issues',
        'csp_inline_scripts' => 'Inline Scripts',
        'csp_inline_styles' => 'Inline Styles',
        'csp_event_handlers' => 'Event Handlers',
        'csp_javascript_urls' => 'JavaScript URLs',
        'csp_section_label' => 'CSP Compliance',
        'csp_violation_inline_script' => 'Inline Script',
        'csp_violation_inline_style' => 'Inline Style',
        'csp_violation_event_handler' => 'Event Handler',
        'csp_violation_javascript_url' => 'JavaScript URL',
        'csp_warning_title' => 'CSP Non-Compliance Warning',
        'csp_warning_message' => 'This plugin contains CSP non-compliant inline scripts/styles. Some features may not work when CSP is enabled in enforce mode.',
        'csp_fix_suggestion' => 'Fix: Change <script> to <script @cspNonce> and <style> to <style @cspNonce>.',
    ],

    // 2段階モーダルフロー
    'two_stage' => [
        'stage1_scan_required_title' => 'Security Scan Required',
        'stage1_scan_required_message' => 'This plugin must be scanned before :action.',
        'stage1_scan_optional_title' => 'Scan Plugin?',
        'stage1_scan_optional_message' => 'Would you like to scan this plugin before :action?',
        'stage1_scanning' => 'Scanning Plugin',
        'stage1_scanning_description' => 'Running security scan.<br>Please wait.',
        'stage1_skip_scan' => 'Skip Scan',
        'stage1_start_scan' => 'Start Scan',
        'stage2_confirm_install' => 'Install this plugin?',
        'stage2_confirm_enable' => 'Enable this plugin?',
        'stage2_blocked_title' => 'Cannot :action',
        'stage2_blocked_message' => 'This plugin does not meet the security requirements and cannot be :action.',
        'stage2_scan_result_heading' => 'Pre-:action Confirmation',
        'stage2_warning_message' => 'This plugin has the following warnings:',
        'action_install' => 'install',
        'action_enable' => 'enable',
        'action_installed' => 'installed',
        'action_enabled' => 'enabled',
        'install_blocked' => 'This plugin cannot be installed because it does not meet the security requirements.',
        'stage2_confirm_action_message' => 'Do you want to proceed with :action?',
        'processing_install' => 'Installing Plugin',
        'processing_enable' => 'Enabling Plugin',
        'processing_install_description' => 'Installing the plugin.<br>Please wait.',
        'processing_enable_description' => 'Enabling the plugin.<br>Please wait.',
    ],
];
