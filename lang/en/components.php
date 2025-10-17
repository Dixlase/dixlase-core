<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
    // Pagination related
    'pagination' => [
        'navigation' => 'Page navigation',
        'page' => 'Page :current of :total',
        'previous' => 'Previous',
        'next' => 'Next',
        'first' => 'First',
        'last' => 'Last',
        'showing' => 'Showing :first to :last of :total results',
        'per_page' => 'Per page',
        'per_page_label' => 'Items',
        'total_count' => 'Total: :total items',
        'total_items' => 'Total :count items',
        'total_pages' => 'Total :count pages',
        'no_results' => 'No matching data found',
        'items_suffix' => ' items',
        'sort_by' => 'Sort by',
        'asc' => 'Asc',
        'desc' => 'Desc',
        'ascending' => 'Ascending (A-Z, Old-New)',
        'descending' => 'Descending (Z-A, New-Old)',
    ],


    // Form related
    'forms' => [
        'placeholder' => [
            'search' => 'Enter search keywords...',
            'email' => 'Enter email address',
            'password' => 'Enter password',
            'name' => 'Enter name',
            'title' => 'Enter title',
            'description' => 'Enter description',
        ],
        'validation' => [
            'required' => 'This field is required',
            'email' => 'Please enter a valid email address',
            'unique' => 'This value already exists',
            'min_length' => 'Please enter at least :min characters',
            'max_length' => 'Please enter no more than :max characters',
            'confirmed' => 'Password confirmation does not match',
        ],
    ],

    // Status related
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'draft' => 'Draft',
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        // Descriptions
        'draft_description' => 'Draft status. Not published.',
        'published_description' => 'Published immediately.',
        'scheduled_description' => 'Published at specified date and time.',
    ],

    // Message related
    'messages' => [
        'success' => 'Operation completed successfully',
        'loading' => 'Loading...',
        'no_data' => 'No data available',
        'confirm_delete' => 'Are you sure you want to delete this?',
        'unsaved_changes' => 'You have unsaved changes',
    ],

    // Table related
    'table' => [
        'no_data' => 'No data available',
        'select_all' => 'Select all',
        'selected_count' => ':count selected',
        'sort_asc' => 'Sort ascending',
        'sort_desc' => 'Sort descending',
        'caption' => 'Data list',
        'unknown_role' => 'Unknown role',
    ],
    
    // Filter related
    'filters' => [
        'search_keyword' => 'Keyword',
        'role_filter' => 'Role Filter',
        'status_filter' => 'Status Filter',
        'clear_button' => 'Clear',
    ],

    // Modal related
    'modal' => [
        'delete_title' => 'Confirm Delete',
        'delete_message' => 'This action cannot be undone. Are you sure you want to delete this?',
    ],

    // Password tools related
    'password_messages' => [
        'strength' => [
            'error' => 'Password does not meet requirements',
            'normal' => 'Normal strength',
            'strong' => 'Strong password',
        ],
        'tooltip' => [
            'generate' => 'Generate',
            'toggle' => 'Toggle visibility',
        ],
        'copied' => 'Password copied!',
        'requirements' => [
            // Static display items
            'length' => '8 or more characters',
            'lowercase' => 'Include at least 1 lowercase letter',
            'number' => 'Include at least 1 number',

            // Dynamic messages
            'length_full' => ':min or more characters (recommended :recommended or more)',
            'length_simple' => ':min or more characters',
            'uppercase_required' => 'Include at least 1 uppercase letter (required)',
            'uppercase_optional' => 'Include uppercase letters (optional)',
            'symbol_required' => 'Include at least 1 symbol (!@#$%^&* etc.) (required)',
            'symbol_optional' => 'Including symbols (!@#$%^&* etc.) makes passwords stronger (optional)',

            // Strength labels
            'weak' => 'Weak',
            'normal' => 'Normal',
            'strong' => 'Strong',
            'very_strong' => 'Very Strong',

            // Labels
            'required_label' => ' (required)',
            'optional_label' => ' (optional)',
        ],
        'error' => 'Password does not meet requirements.',
    ],
];
