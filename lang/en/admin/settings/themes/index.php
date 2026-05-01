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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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
    'heading' => 'Theme Management',
    'description' => 'Manage installed themes, add new themes, and switch themes.',
    'installed_heading' => 'Installed Themes',
    'update_available' => 'v:version available',
    'updates' => [
        'check' => 'Check Updates',
        'checking' => 'Checking...',
        'all_up_to_date' => 'All themes are up to date.',
        'updates_found' => ':count update(s) available.',
        'no_update' => 'No update available for this theme.',
        'update_button' => 'Update',
        'update_to_version' => 'Update to v:version',
        'update_success' => 'Theme ":name" has been updated to v:version.',
        'update_failed' => 'Theme update failed: :error',
        'update_all_button' => 'Update All',
        'update_all_confirm_title' => 'Update All Themes',
        'update_all_confirm_message' => 'Update :count theme(s) to their latest versions in one batch. Continue?',
        'update_all_running' => 'Updating themes sequentially. This may take a moment.',
        'update_all_summary' => ':succeeded of :total succeeded, :failed failed',
    ],
    'uninstalled_heading' => 'Uninstalled Themes',
    'title' => 'Themes',
    'available_themes' => 'Available Themes',
    'currently_active' => 'Currently Active',
    'activate_confirm' => 'Do you want to activate this theme?',
    'delete_confirm' => 'Are you sure you want to delete this?',
    'activate_button' => 'Activate',
    'delete_button' => 'Delete',
    'settings_button' => 'Settings',
    'table' => [
        'caption' => 'Installed Themes List',
        'name' => 'Theme Name',
    ],
    'uninstalled_table' => [
        'caption' => 'Uninstalled Themes List',
    ],
    'no_themes' => 'No themes are installed.',
    'no_themes_description' => 'Add themes to customize your site\'s appearance.',
    'add_theme' => 'Add Theme',
    'uninstalled_description' => 'These themes have files present but are not yet installed.',
    'uninstall' => [
        'confirm_title' => 'Uninstall Confirmation',
        'confirm_message' => 'Do you want to uninstall theme [{name}]?',
        'remove_data_checkbox' => 'Delete database tables created during theme installation.<br><br><span class="text-red-600 font-semibold">Warning! Deleting tables will lose data created by the theme!</span>',
    ],
    'install' => [
        'confirm_title' => 'Install Confirmation',
        'confirm_message' => 'Do you want to install theme [{name}]?',
    ],
    'switch' => [
        'confirm_title' => 'Activation Confirmation',
        'confirm_message' => 'Do you want to activate theme [{name}]?',
    ],
    'delete' => [
        'confirm_title' => 'Delete Confirmation',
        'confirm_message' => 'Do you want to permanently delete theme [{name}] files and folders? This action cannot be undone.',
    ],
    'audit' => [
        'invalid_slug' => 'Invalid theme slug.',
        'completed' => 'Theme scan completed.',
        'failed' => 'Theme scan failed.',
        'audit_all_button' => 'Re-scan All Themes',
        'audit_all_confirm_title' => 'Re-scan All Themes',
        'audit_all_confirm_message' => 'Sequentially scan all installed and uninstalled themes. This may take a moment.',
        'audit_all_summary' => 'Scanned :succeeded of :total theme(s) (:failed failed).',
    ],

    // Scan freshness badges
    'scan_status' => [
        'unscanned' => 'Not scanned — please run a scan',
        'expired' => 'Scan expired (last scanned :age days ago / max :max)',
        'files_changed' => 'Files changed — re-scan recommended',
    ],

    // Badge Labels (for card display)
    'badge_labels' => [
        'health' => 'Health',
        'health_full' => 'Health Status',
        'signature' => 'Sign',
        'permission' => 'Perm',
        'csp' => 'CSP',
        'preset' => 'Ext',
        'operation' => 'Op',
    ],

    // CSP Mode Badge Labels
    'csp_mode' => [
        'development' => 'Dev',
        'standard' => 'Std',
        'strict' => 'Strict',
        'not_checked' => 'N/A',
    ],

    // Security Preset Compatibility Badge Labels
    'preset_badge' => [
        'development' => 'Dev',
        'balanced' => 'Balanced',
        'strict' => 'Strict',
        'custom' => 'Current',
        'not_verified' => 'N/A',
    ],

    // Health Status
    'health_status' => [
        'healthy' => 'Healthy',
        'healthy_description' => 'No mismatches found between declared permissions, signature, and configuration.',
        'advisory' => 'Advisory',
        'advisory_description' => 'Minor issues found. No immediate impact on operation, but review is recommended.',
        'needs_attention' => 'Needs Attention',
        'needs_attention_description' => 'Important issues found. Please review before activation or operation.',
        'not_verified' => 'Not Verified',
        'not_verified_description' => 'Verification information is insufficient (not scanned, no permissions, no signature, etc.).',
    ],

    // Verification Status
    'verification' => [
        // Signature
        'signature_valid' => 'Signed',
        'signature_unsigned' => 'Unsigned',
        'signature_invalid' => 'Invalid',
        'signature_pending' => 'Pending',
        'signature_not_scanned' => 'N/A',
        // Permission
        'permission_ok' => 'OK',
        'permission_undefined' => 'Undefined',
        'permission_mismatch' => 'Mismatch',
        'permission_not_scanned' => 'N/A',
        // CSP
        'csp_ready' => 'CSP Ready',
        'csp_compatible' => 'CSP Compatible',
        'csp_inline_required' => 'CSP N/A',
        'csp_not_checked' => 'CSP N/A',
    ],

    // Operation Status (traffic light)
    'operation_status' => [
        'ok' => 'Fully Operational',
        'caution' => 'Caution',
        'blocked' => 'Blocked',
        'unknown' => 'Unknown',
    ],

    // CSP Compliance
    'csp' => [
        'status_label' => 'CSP Compliance',
        'ready_tooltip' => 'This theme is fully CSP compliant. Works in all CSP modes.',
        'inline_required_tooltip' => 'This theme requires inline JavaScript. Will not work in CSP strict mode.',
    ],

    'permissions' => [
        'health_status' => 'Health Status',
        'health_healthy' => 'Healthy',
        'health_warning' => 'Warning',
        'health_needs_attention' => 'Needs Attention',
        'health_not_verified' => 'Not Verified',
        'health_status_healthy' => 'Healthy',
        'health_status_advisory' => 'Advisory',
        'health_status_needs_attention' => 'Needs Attention',
        'health_status_not_verified' => 'Not Verified',
        'unknown' => 'Undefined',
        'unknown_warning' => 'Permission information is not defined. It is unknown what operations this theme performs. Please confirm it was obtained from a trusted source.',
        'audit_mismatch_title' => 'Permission Mismatch',
        'audit_mismatch_warning' => 'Permissions declared in theme.json do not match actual code.',
        'audit_undeclared_usage' => 'Undeclared Feature Usage',
        'audit_unused_declaration' => 'Unused Permission Declaration',
        'audit_button' => 'Scan',
        'audit_button_rescan' => 'Rescan',
        'audit_scanning' => 'Scanning...',
        'audit_scanning_title' => 'Scanning Theme',
        'audit_scanning_description' => 'Running a security scan on the theme.<br>Please wait until it completes.',
        'audit_not_scanned' => 'Not Scanned',
        'audit_last_scanned' => 'Last Scanned',
        'audit_result_title' => 'Scan Results',
        'audit_mismatch_found' => 'Permission mismatch detected',
        'audit_no_issues' => 'No issues detected',
        'audit_stats' => 'Check Items',
        'audit_matches' => 'Matches',
        'audit_mismatches' => 'Mismatches',
        'audit_mismatch_badge' => 'Mismatch',
        'no_permissions' => 'Permission information is not defined',
        'details_title' => 'Theme Details',
        'no_special_permissions' => 'No special permissions',
        'no_permissions_defined' => 'Permission information is not defined. This theme\'s permissions are unknown.',
        'signature_status' => 'Signature Status',
        'signature_official' => 'Official',
        'signature_verified' => 'Verified',
        'signature_partner' => 'Partner',
        'signature_signed' => 'Signed',
        'signature_valid' => 'Signed',
        'signature_invalid' => 'Invalid Signature',
        'signature_unsigned' => 'Unsigned',
        'signature_invalid_warning' => '⚠️ This theme\'s signature is invalid. It may have been tampered with.',
        'signature_unsigned_info' => 'This theme is not signed. Please confirm it was obtained from a trusted source.',
        'signed_by' => 'Signed By',
        'permission_info' => 'Permission Information',
        'category_database' => 'Database',
        'category_storage' => 'Storage',
        'category_settings' => 'Settings',
        'category_assets' => 'Assets',
        'category_system' => 'System',
        'perm_own_tables' => 'Own Tables',
        'perm_core_tables_read' => 'Core Tables (Read)',
        'perm_core_tables_write' => 'Core Tables (Write)',
        'perm_own_directory' => 'Own Directory',
        'perm_public_uploads' => 'Public Uploads',
        'perm_temp_files' => 'Temp Files',
        'perm_read_core' => 'Read Core Settings',
        'perm_write_own' => 'Write Own Settings',
        'perm_custom_css' => 'Custom CSS',
        'perm_custom_js' => 'Custom JS',
        'perm_external_resources' => 'External Resources',
        'perm_register_shortcodes' => 'Shortcodes',
        'perm_register_middleware' => 'Middleware',
        'perm_register_commands' => 'Commands',
        'perm_register_blade_directives' => 'Blade Directives',
        'perm_modify_routes' => 'Modify Routes',
        'attention_reasons_title' => 'Reasons for Attention',
        'attention_reason_storage_public_uploads' => 'Uses public directory upload permission',
        'attention_reason_assets_external_resources' => 'Uses external resource loading permission',
        'attention_reason_assets_external_resources_trusted' => 'Uses trusted external resources (:domains)',
        'attention_reason_database_core_tables_read' => 'Uses core table read permission',
        'attention_reason_database_core_tables_write' => 'Uses core table write permission',
        'attention_reason_settings_read_core' => 'Uses core settings read permission',
        'attention_reason_system_register_middleware' => 'Uses middleware registration permission',
        'attention_reason_system_register_commands' => 'Uses command registration permission',
        'attention_reason_system_register_blade_directives' => 'Uses Blade directive registration permission',
        'attention_reason_system_modify_routes' => 'Uses route modification permission',
        'attention_reason_mismatch_undeclared_usage' => 'Undeclared permission usage detected (:count occurrences)',
        'permission_consistency_title' => 'Permission Consistency',
        'total_risk_score' => 'Total Risk Score',
        'install_warning_title' => 'Pre-Installation Confirmation',
        'install_warning_notice' => 'This theme has the following items to confirm:',
        'install_warning_undefined' => 'Permission information is undefined',
        'install_warning_unsigned' => 'Not signed',
        'install_warning_mismatch' => 'Permission declaration and code do not match',
        'install_warning_confirm' => 'Do you want to install understanding the above?',
        'install_warning_risk' => 'This theme has the following notes:',
        'warning_not_scanned' => 'Scan has not been run',
        'risk_medium' => 'Medium health risk',
        'risk_high' => 'High health risk',
        'enable_warning_title' => 'Pre-Activation Confirmation',
        'enable_warning_message' => 'This theme has the following notes:',
        'enable_warning_confirm' => 'Do you want to activate understanding the above?',
        'enable_warning_invalid_signature' => 'Invalid signature (possible tampering)',
        'enable_warning_needs_attention' => 'Contains permissions that need attention',
        'total_evaluation' => 'Overall Evaluation',
        'health_score_display' => 'Score: :score/100',
        'signature_section_label' => 'Signature',
        'health_issue_signature_unsigned' => 'Signature: Unsigned',
        'health_issue_signature_invalid' => 'Signature: Invalid',
        'health_issue_permission_undefined' => 'Permission: Undefined',
        'health_issue_permission_undeclared_minor' => 'Permission: Undeclared usage (minor)',
        'health_issue_permission_undeclared_major' => 'Permission: Undeclared usage (major)',
        'health_issue_permission_unused' => 'Permission: Unused declaration',
        'health_issue_csp_inline_css_required' => 'CSP: Inline CSS required',
        'health_issue_csp_inline_js_required' => 'CSP: Inline JS required',
        'health_issue_csp_violation_strict' => 'CSP: Violation (strict mode)',
        'health_issue_csp_violation_standard' => 'CSP: Violation (standard mode)',
        'health_issue_dangerous_api_exec' => 'Dangerous API detected',
        'health_issue_scan_not_performed' => 'Scan: Not performed',
        'health_issue_scan_outdated' => 'Scan: Outdated',
        'csp_status' => 'CSP Compliance',
        'csp_section_label' => 'CSP Compliance',
        'csp_compliant' => 'Compliant',
        'csp_not_compliant' => 'Non-compliant',
        'csp_inline_scripts' => 'Inline Scripts',
        'csp_inline_styles' => 'Inline Styles',
        'csp_event_handlers' => 'Event Handlers',
        'csp_javascript_urls' => 'JavaScript URLs',
        'csp_violation_inline_script' => 'Inline Script',
        'csp_violation_inline_style' => 'Inline Style',
        'csp_violation_event_handler' => 'Event Handler',
        'csp_violation_javascript_url' => 'JavaScript URL',
    ],
];
