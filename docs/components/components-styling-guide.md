# Dixlase Components Styling Guide

This guide explains how to use the Dixlase component style system.

## Overview

Dixlase provides component styles that can be shared across the admin panel and plugins. These styles are defined in `resources/src/common/scss/_components.scss` and are available for plugin developers as well.

## File Structure

```
resources/
├── src/
│   ├── common/scss/
│   │   ├── _components.scss    # Shared component styles
│   │   ├── _tailwind-custom.scss
│   │   └── style.scss          # Main style file
│   └── admin/scss/
│       └── _admin.scss         # Admin-only styles
└── views/
    └── components/             # Blade components
```

## Available Component Styles

### 1. Navigation Buttons

```html
<!-- Basic navigation button -->
<button class="nav-button">Basic Button</button>

<!-- Active state buttons -->
<button class="nav-button nav-button--blue nav-button--active">Active (Blue)</button>
<button class="nav-button nav-button--green nav-button--active">Active (Green)</button>
<button class="nav-button nav-button--red nav-button--active">Active (Red)</button>
<button class="nav-button nav-button--yellow nav-button--active">Active (Yellow)</button>
```

### 2. Action Buttons

```html
<button class="action-button action-button--primary">Primary</button>
<button class="action-button action-button--success">Success</button>
<button class="action-button action-button--danger">Danger</button>
<button class="action-button action-button--warning">Warning</button>
```

### 3. Pagination

```html
<div class="pagination">
    <button class="pagination-button pagination-button--disabled">Previous</button>
    <span class="pagination-number pagination-number--current">1</span>
    <span class="pagination-number">2</span>
    <span class="pagination-ellipsis">...</span>
    <span class="pagination-number">10</span>
    <button class="pagination-button">Next</button>
</div>
<div class="pagination-info">1-10 of 100 items</div>
```

### 4. Modals

```html
<div class="modal modal--animate modal--visible">
    <div class="modal-overlay">
        <div class="modal-container">
            <div class="modal-content">
                <div class="modal-icon modal-icon--warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="modal-title">Confirmation</h3>
                <div class="modal-message">
                    <p>Are you sure you want to perform this action?</p>
                </div>
                <div class="modal-actions">
                    <button class="modal-button modal-button--confirm red">Execute</button>
                    <button class="modal-button modal-button--cancel">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
```

### 5. Messages

```html
<div class="message success">
    <p>The operation completed successfully.</p>
</div>

<div class="message warning">
    <p>Attention is required.</p>
</div>

<div class="message error">
    <p>An error has occurred.</p>
</div>

<div class="message info">
    <p>Here is some information for you.</p>
</div>
```

### 6. Tables

```html
<table class="component-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>Sample</td>
            <td>
                <button class="action-button action-button--primary">Edit</button>
            </td>
        </tr>
    </tbody>
</table>
```

### 7. Responsive Tables

```html
<div class="responsive-table">
    <table class="component-table">
        <!-- Table content -->
    </table>
</div>
```

## Using in Plugins

### 1. Importing Styles

Import the shared styles in your plugin's SCSS file:

```scss
// Plugin SCSS file
@use '../../../common/scss/components';

.your-plugin-styles {
    // Plugin-specific styles
}
```

### 2. Usage in Blade Templates

```blade
{{-- Plugin Blade template --}}
<div class="your-plugin-container">
    <button class="action-button action-button--primary">
        Primary Button
    </button>

    <div class="message success">
        <p>Success message</p>
    </div>
</div>
```

### 3. Adding Custom Styles

Plugin-specific styles can be created by extending the shared styles:

```scss
// Plugin-specific button style
.your-plugin-button {
    @extend .action-button;
    @extend .action-button--primary;

    // Additional customization
    border-radius: 8px;
    font-weight: bold;
}
```

## Dark Mode Support

All component styles support dark mode. They switch automatically using the Tailwind CSS `dark:` prefix.

## Best Practices

1. **Prefer using existing component styles**
   - Before creating new styles, check if existing component styles can be used

2. **Maintain consistency**
   - Match colors, sizes, and spacing to the existing design system

3. **Responsive support**
   - Design mobile-first and use appropriate breakpoints

4. **Accessibility**
   - Consider proper contrast ratios, focus states, and keyboard navigation

## Customization

Plugin developers can create their own variations based on the shared styles:

```scss
// Custom action button
.custom-action-button {
    @extend .action-button;

    &--custom-color {
        @apply bg-purple-600 hover:bg-purple-700 text-white;
    }
}
```

## Support

If you have questions or suggestions about styles, please contact the development team.

---

This guide serves as a reference for effectively using the Dixlase component style system. It is updated regularly, so please check for the latest information.
