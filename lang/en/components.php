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
        'per_page_label' => 'Items per page',
        'total_items' => 'Total :count items',
        'total_pages' => 'Total :count pages',
        'no_results' => 'No matching data found',
    ],


    // Form related
    'forms' => [
        'required' => 'Required',
        'optional' => 'Optional',
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
            'min_length' => 'Please enter at least :min characters',
            'max_length' => 'Please enter no more than :max characters',
        ],
    ],

    // Status related
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'published' => 'Published',
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    // Message related
    'messages' => [
        'success' => 'Operation completed successfully',
        'error' => 'An error occurred',
        'warning' => 'Warning',
        'info' => 'Information',
        'loading' => 'Loading...',
        'no_data' => 'No data available',
        'confirm_delete' => 'Are you sure you want to delete this?',
        'unsaved_changes' => 'You have unsaved changes',
    ],

    // Table related
    'table' => [
        'actions' => 'Actions',
        'no_data' => 'No data available',
        'select_all' => 'Select all',
        'selected_count' => ':count selected',
        'sort_asc' => 'Sort ascending',
        'sort_desc' => 'Sort descending',
    ],

    // Modal related
    'modal' => [
        'close' => 'Close',
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'delete_title' => 'Confirm Delete',
        'delete_message' => 'This action cannot be undone. Are you sure you want to delete this?',
    ],
];
