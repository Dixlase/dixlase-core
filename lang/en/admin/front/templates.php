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

---

## Markdown Tag Samples

### Text Formatting

This is **bold**, *italic*, ***bold italic***, and ~~strikethrough~~ text.

`Inline code` is also supported.

> This is a blockquote. It can span multiple lines and is useful for highlighting important content.

### Nested Lists

- Item 1
- Item 2
  - Sub-item 2-1
  - Sub-item 2-2
- Item 3

### Ordered List

1. First item
2. Second item
3. Third item

### Links

[Dixlase Official Website](https://example.com)

### Table

| Column 1 | Column 2 | Column 3 |
|----------|----------|----------|
| A1 | B1 | C1 |
| A2 | B2 | C2 |
| A3 | B3 | C3 |

### Code Block

```javascript
function hello() {
    console.log("Hello, Dixlase!");
}
```

### Task List

- [x] Completed task
- [ ] Incomplete task
- [ ] Another incomplete task

## Image Sample

![Sample Image](data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='600'%20height='300'%20viewBox='0%200%20600%20300'%3E%3Crect%20fill='%234f46e5'%20width='600'%20height='300'/%3E%3Ctext%20x='50%25'%20y='50%25'%20dominant-baseline='middle'%20text-anchor='middle'%20fill='white'%20font-family='system-ui'%20font-size='24'%3ESample%20Image%20(600x300)%3C/text%3E%3C/svg%3E)
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

<hr>

<h2>HTML Tag Samples</h2>

<h3>Text Formatting</h3>
<p>This is <strong>bold</strong>, <em>italic</em>, <u>underline</u>, <s>strikethrough</s>, and <mark>highlight</mark> text.</p>
<p><code>Inline code</code> is also supported. <a href="#">Link test</a>.</p>

<blockquote>
  <p>This is a blockquote. It can span multiple lines and is useful for highlighting important content.</p>
</blockquote>

<h3>Nested Lists</h3>
<ul>
    <li>Item 1</li>
    <li>Item 2
        <ul>
            <li>Sub-item 2-1</li>
            <li>Sub-item 2-2</li>
        </ul>
    </li>
    <li>Item 3</li>
</ul>

<h3>Ordered List</h3>
<ol>
    <li>First item</li>
    <li>Second item</li>
    <li>Third item</li>
</ol>

<h3>Table</h3>
<table>
    <thead>
        <tr><th>Column 1</th><th>Column 2</th><th>Column 3</th></tr>
    </thead>
    <tbody>
        <tr><td>A1</td><td>B1</td><td>C1</td></tr>
        <tr><td>A2</td><td>B2</td><td>C2</td></tr>
    </tbody>
</table>

<h3>Images</h3>
<p>Inline image with caption:</p>
<figure>
    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='300' viewBox='0 0 600 300'%3E%3Crect fill='%234f46e5' width='600' height='300'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-family='system-ui' font-size='24'%3ESample Image (600x300)%3C/text%3E%3C/svg%3E" alt="Sample image" style="max-width:100%;height:auto;border-radius:0.5rem;">
    <figcaption>Figure 1: A sample placeholder image</figcaption>
</figure>

<p>Side-by-side images:</p>
<div style="display:flex;gap:1rem;flex-wrap:wrap;">
    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='280' height='180' viewBox='0 0 280 180'%3E%3Crect fill='%230d9488' width='280' height='180'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-family='system-ui' font-size='16'%3EPhoto 1%3C/text%3E%3C/svg%3E" alt="Photo 1" style="border-radius:0.5rem;">
    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='280' height='180' viewBox='0 0 280 180'%3E%3Crect fill='%23d97706' width='280' height='180'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-family='system-ui' font-size='16'%3EPhoto 2%3C/text%3E%3C/svg%3E" alt="Photo 2" style="border-radius:0.5rem;">
</div>

<h3>Code Block</h3>
<pre><code>function hello() {
    console.log("Hello, Dixlase!");
}</code></pre>

<h3>Custom Styled Card</h3>
<div class="custom-card">
    <h3>Sample Card</h3>
    <p>This card demonstrates custom CSS styling.</p>
</div>

<div class="custom-card accent">
    <h3>Accent Card</h3>
    <p>A different style variation.</p>
</div>
HTML,

        'content_css' => <<<'CSS'
/* Custom card styles */
.custom-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.custom-card.accent {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.custom-card h3 {
    margin-top: 0;
    font-size: 1.25rem;
}
CSS,

        'content_js' => <<<'JS'
// Sample: Log page load
console.log('Front page loaded successfully.');
JS,
    ],
];
