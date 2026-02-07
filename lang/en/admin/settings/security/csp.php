<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'Content Security Policy',
    'title' => 'Content Security Policy (CSP)',
    'description' => 'CSP is a security feature that instructs browsers which resources can be loaded and executed. It prevents XSS attacks and unauthorized script execution.',
    'enabled' => 'Enable CSP',
    'enabled_help' => 'Add Content-Security-Policy header to responses.',
    'mode' => 'CSP Mode',
    'mode_help' => 'Choose the balance between security level and development ease.',
    'recommended' => 'Recommended',
    'mode_development' => 'Development Mode',
    'mode_development_desc' => 'Best for plugin/theme development. All scripts work, violations are logged.',
    'mode_development_feature1' => 'Inline JS/CSS, onclick, etc. all allowed',
    'mode_development_feature2' => 'Report-Only mode logs violations',
    'mode_development_feature3' => 'Migrate to Standard mode after development',
    'mode_standard' => 'Standard Mode',
    'mode_standard_desc' => 'Recommended for production. Only nonce-based inline allowed, balancing security and compatibility.',
    'mode_standard_feature1' => 'Raw <script> tags are blocked',
    'mode_standard_feature2' => '@dixScript helpers are allowed',
    'mode_standard_feature3' => 'Most plugins/themes work',
    'mode_standard_feature4' => 'Allows unsafe-eval for Alpine.js usage',
    // 'mode_strict' => 'Strict Mode', // Not implemented in initial version
    // 'mode_strict_desc' => 'Maximum security level. No inline scripts allowed at all.',
    // 'mode_strict_feature1' => 'Inline JS/CSS completely forbidden',
    // 'mode_strict_feature2' => 'Only CSP Ready plugins/themes work',
    // 'mode_strict_feature3' => 'Plugins with requires_inline_js: true cannot be enabled',
    'log_violations' => 'Log Violations',
    'log_violations_help' => 'Record CSP violations to log file (csp_violations.log).',
    'exclude_dev_tools' => 'Exclude Dev Tool Violations',
    'exclude_dev_tools_help' => 'Exclude CSP violations from development tools (Vite dev server, Windsurf/MCP browser preview, etc.) from logs.',
    'trusted_domains' => 'Trusted Domains',
    'trusted_domains_help' => 'Enter domains allowed to load external resources, one per line. You can add external CDNs required by plugins or themes.',
    'trusted_domains_placeholder' => 'https://cdn.example.com
https://fonts.googleapis.com
https://api.example.com',
    'denied_domains' => 'Denied Domains',
    'denied_domains_help' => 'Enter domains to <strong>always block</strong> from loading external resources, one per line.<br>Even if plugins or themes try to use these domains, they will be blocked by CSP.<br>Wildcards (e.g., <code>*.example.com</code>) are also supported.',
    'denied_domains_placeholder' => 'google-analytics.com
*.doubleclick.net
tracking.example.com',
    'blocklist_check_title' => 'Blocklist Check',
    'blocklist_check_description' => 'Check if external domains in plugins/themes are on known malicious domain lists during installation or activation.',
    'blocklist_check_enabled' => 'Enable blocklist check',
    'blocklist_action_label' => 'Action on detection:',
    'blocklist_action_warn' => 'Warn only',
    'blocklist_action_warn_desc' => '(Show warning but allow installation/activation)',
    'blocklist_action_block' => 'Block',
    'blocklist_action_block_desc' => '(Deny installation/activation)',
    'blocklist_check_categories' => 'Categories to check:',
    'blocklist_sources_show' => 'Show source URLs',
    'blocklist_warning_title' => 'Dangerous domains detected',
    'blocklist_warning_message' => 'This extension uses the following dangerous domains:',
    'blocklist_blocked_title' => 'Installation blocked',
    'blocklist_blocked_message' => 'This extension cannot be installed because it uses dangerous domains:',
    'custom_directives' => 'Custom Directives',
    'custom_directives_help' => 'For advanced configuration, specify custom directives in JSON format.',
    'custom_directives_placeholder' => '{"script-src": ["https://example.com"], "connect-src": ["https://api.example.com"]}',
    'what_is_csp' => 'What is CSP?',
    'what_is_csp_description' => 'Content Security Policy (CSP) is a security feature that restricts which scripts can be executed and which resources can be loaded on a web page. This significantly reduces the risk of XSS (Cross-Site Scripting) attacks and data leakage.',
    'nonce_explanation' => 'Dixlase uses a nonce (one-time token) approach, ensuring only authorized inline scripts are executed.',
    'badge_csp_ready' => 'CSP Ready',
    'badge_inline_required' => 'Inline JS Required',
    'badge_csp_ready_tooltip' => 'This plugin is fully CSP compatible',
    'badge_inline_required_tooltip' => 'This plugin requires inline JS. Cannot be used in strict mode.',
    // 'strict_mode_blocked' => 'Cannot enable in strict mode', // Not implemented in initial version
    // 'strict_mode_blocked_reason' => 'This plugin requires inline JS and cannot be enabled in CSP strict mode.',
    'development_mode_warning' => 'CSP development mode allows all scripts, which poses security risks. It is recommended to use standard mode in production environments.',
    'settings_updated' => 'CSP settings have been updated.',
    
    // Confirmation Modal
    'confirmation_modal_title' => 'Confirm CSP Settings',
    'confirmation_modal_message' => 'CSP settings have been changed.<br><br>If the page is displaying correctly, please click <strong>"Use This Setting"</strong>.<br><br><strong class="text-red-600 dark:text-red-400">Settings will automatically revert in :seconds seconds.</strong>',
    'confirmation_modal_confirm' => 'Use This Setting',
    'confirmation_modal_cancel' => 'Revert',
    'settings_confirmed' => 'CSP settings have been confirmed.',
    'settings_rolled_back' => 'CSP settings have been reverted.',
    'no_previous_settings' => 'Previous settings not found.',
    'rollback_warning' => 'Settings will automatically revert in :seconds seconds if not confirmed.',
    
    // Safe Mode
    'safe_mode_banner_title' => '⚠️ CSP Safe Mode is Active',
    'safe_mode_banner_message' => 'CSP is disabled. This poses a security risk. Please disable safe mode after completing your configuration.',
    'safe_mode_go_to_settings' => 'Go to CSP Settings',
    'safe_mode_disable' => 'Disable Safe Mode',
    'safe_mode_disabled' => 'CSP safe mode has been disabled.',
    'safe_mode_admin_only' => 'CSP safe mode is only available to administrators.',
];
