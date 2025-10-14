<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dixlase Default Theme Language Lines (English)
    |--------------------------------------------------------------------------
    */

    'name' => 'Dixlase Default Theme',
    'description' => 'A simple and modern default theme',

    // Navigation
    'navigation' => [
        'home' => 'Home',
        'about' => 'About',
        'services' => 'Services',
        'contact' => 'Contact',
        'toggle_menu' => 'Toggle menu',
    ],

    // Footer
    'footer' => [
        'about_title' => 'About Us',
        'links' => 'Links',
        'contact' => 'Contact',
        'copyright' => '© :year :name. All rights reserved.',
        'powered_by' => 'Powered by Dixlase',
    ],
    
    // Admin Settings
    'admin' => [
        'settings' => [
            'title' => 'Theme Settings',
            'updated_successfully' => 'Theme settings updated successfully',
            
            // Header Section
            'header' => [
                'title' => 'Header Settings',
                'logo_url' => 'Logo URL',
                'logo_url_help' => 'Logo image URL (leave empty to display text logo)',
                'logo_text' => 'Logo Text',
                'logo_text_help' => 'Text displayed as site name',
            ],
            
            // Hero Section
            'hero' => [
                'title' => 'Hero Section Settings',
                'background_image' => 'Background Image URL',
                'background_image_help' => 'Full-screen hero area background image (leave empty for gradient)',
                'main_title' => 'Main Title',
                'main_title_help' => 'Hero area main heading',
                'sub_title' => 'Sub Title',
                'sub_title_help' => 'Hero area description text',
                'button_text' => 'Primary Button Text',
                'button_link' => 'Primary Button Link',
                'button_secondary_text' => 'Secondary Button Text',
                'button_secondary_link' => 'Secondary Button Link',
            ],
            
            // Footer Section
            'footer' => [
                'title' => 'Footer Settings',
                'description' => 'Footer Description',
                'description_help' => 'Description text displayed in footer',
                'links' => 'Footer Links',
                'links_help' => 'List of links to display in footer',
                'link_title' => 'Link Title',
                'link_url' => 'Link URL',
                'add_link' => 'Add Link',
                'remove_link' => 'Remove Link',
                'copyright' => 'Copyright',
                'copyright_help' => 'Copyright text displayed in footer',
                'sns_title' => 'SNS Links',
                'sns_facebook' => 'Facebook URL',
                'sns_twitter' => 'Twitter URL',
                'sns_instagram' => 'Instagram URL',
                'sns_linkedin' => 'LinkedIn URL',
                'sns_youtube' => 'YouTube URL',
            ],
            
            // Colors Section
            'colors' => [
                'title' => 'Color Settings',
                'primary' => 'Primary Color',
                'secondary' => 'Secondary Color',
                'accent' => 'Accent Color',
            ],
        ],
    ],

    // Buttons
    'buttons' => [
        'learn_more' => 'Learn More',
        'get_started' => 'Get Started',
        'contact_us' => 'Contact Us',
        'back_to_home' => 'Back to Home',
        'back_to_previous' => 'Back to Previous',
    ],

    // 404 Page
    '404' => [
        'title' => 'Page Not Found',
        'message' => 'The page you are looking for does not exist or has been moved.',
        'back_home' => 'Back to Home',
        'back_previous' => 'Back to Previous',
    ],

    // Front Page
    'frontpage' => [
        'welcome' => 'Welcome to :name',
        'description' => 'You can customize this page with the front page builder',
        'go_to_admin' => 'Go to Admin',
    ],
];
