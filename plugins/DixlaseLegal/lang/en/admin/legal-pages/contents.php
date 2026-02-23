<?php

/**
 * This file is part of Dixlase Legal.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 */

return [
    'heading' => 'Legal Page Contents',
    'description' => 'Manage content for each legal page type and language. Create and edit content in HTML or Markdown format.',

    'table_page_type' => 'Page Type',
    'edit_link' => 'Edit',
    'create_link' => 'Create',

    'title_label' => 'Title',
    'title_placeholder' => 'Enter page title',
    'editor_type_label' => 'Editor Type',
    'editor_html_description' => 'Write content directly in HTML. Full control over formatting.',
    'editor_markdown_description' => 'Write content in Markdown format. Simple and easy to read.',
    'content_label' => 'Content',
    'content_placeholder' => 'Enter page content...',
    'status_label' => 'Status',
    'published_at_label' => 'Publish Date',
    'published_at_help' => 'Set the date and time when this content will be automatically published.',

    'save_success' => 'Legal page content has been saved.',

    'confirm_title' => 'Save Legal Page Content',
    'confirm_message' => 'Are you sure you want to save this legal page content?',

    'validation' => [
        'title_max' => 'Title must not exceed 255 characters.',
        'content_max' => 'Content must not exceed 500,000 characters.',
        'editor_type_required' => 'Please select an editor type.',
        'editor_type_in' => 'Invalid editor type selected.',
        'status_required' => 'Please select a status.',
        'status_in' => 'Invalid status selected.',
        'published_at_required' => 'Publish date is required when status is scheduled.',
        'published_at_date' => 'Please enter a valid date and time.',
    ],
];
