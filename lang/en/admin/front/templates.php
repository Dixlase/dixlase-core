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
    'main_content' => [
        'content_markdown' => <<<'MARKDOWN'
# Welcome to Our Website

Thank you for visiting our website. This is the front page where you can introduce your site to visitors.

## About Us

Write a brief introduction about your organization, business, or project here.

## Our Services

- **Service 1**: Description of your first service
- **Service 2**: Description of your second service
- **Service 3**: Description of your third service

## Contact

Feel free to reach out to us:

- Email: [email@example.com]
- Phone: [Your Phone Number]
- Address: [Your Address]
MARKDOWN,
        'content_html' => <<<'HTML'
<h1>Welcome to Our Website</h1>
<p>Thank you for visiting our website. This is the front page where you can introduce your site to visitors.</p>

<h2>About Us</h2>
<p>Write a brief introduction about your organization, business, or project here.</p>

<h2>Our Services</h2>
<ul>
    <li><strong>Service 1</strong>: Description of your first service</li>
    <li><strong>Service 2</strong>: Description of your second service</li>
    <li><strong>Service 3</strong>: Description of your third service</li>
</ul>

<h2>Contact</h2>
<p>Feel free to reach out to us:</p>
<ul>
    <li>Email: [email@example.com]</li>
    <li>Phone: [Your Phone Number]</li>
    <li>Address: [Your Address]</li>
</ul>
HTML,
    ],
];
