# Save Button and Modal Usage

To comply with Dixlase's strict CSP mode, three variants of save button and modal components are provided.

## Component List

### 1. Alpine.js Version (Legacy)
- **Component**: `<x-admin.save-button>`
- **Modal**: `<x-ui-modal>`
- **Use case**: Existing pages that use Alpine.js
- **CSP**: Development mode / Standard mode

### 2. Vanilla JS Version (Strict CSP Compliant)
- **Component**: `<x-admin.save-button-vanilla>`
- **Modal**: `<x-ui-modal-vanilla>`
- **Use case**: Pages that require strict CSP compliance
- **CSP**: Strict mode compliant

## Usage

### Alpine.js Version (Legacy)

```blade
@extends('layouts.admin')

@section('content')
<div>
    <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <!-- Form fields -->
    </form>
</div>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="settings-form"
    />
@endsection
```

### Vanilla JS Version (Recommended)

```blade
@extends('layouts.admin')

@section('content')
<div>
    <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <!-- Form fields -->
    </form>
</div>
@endsection

@section('save')
    <x-admin.save-button-vanilla
        modalId="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirmLabel="__('common.save')"
        :cancelLabel="__('common.cancel')"
        form="settings-form"
    />
@endsection
```

## Parameter Reference

### Common Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `label` | string | `__('common.save')` | Button label |
| `title` | string | `__('common.save_confirmation_title')` | Modal title |
| `message` | string | `__('common.save_confirmation_message')` | Modal message |
| `confirmLabel` | string | `__('common.save')` | Confirm button label |
| `cancelLabel` | string | `__('common.cancel')` | Cancel button label |
| `backUrl` | string | null | Back button URL |
| `backLabel` | string | `__('common.back')` | Back button label |
| `iconType` | string | `'info'` | Icon type (info, warning, danger, success) |
| `confirmColor` | string | `'blue'` | Confirm button color (blue, red, green, yellow) |

### Alpine.js / Vanilla JS Version Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `form` | string | null | ID of the form to submit |
| `modalId` | string | `'confirmationModal'` | Modal ID (Vanilla version) |
| `id_confirmation` | string | `'confirmationModal'` | Modal ID (Alpine version) |

## Layout Placement

All versions can be placed using `@section('save')` in the same way.

```blade
@section('save')
    <!-- Alpine.js version -->
    <x-admin.save-button ... />

    <!-- Vanilla JS version -->
    <x-admin.save-button-vanilla ... />

@endsection
```

The layout file (`layouts/admin.blade.php`) contains the following placement:

```blade
@hasSection('save')
    <div class="sticky bottom-0 z-30 backdrop-blur-sm bg-white/75 dark:bg-gray-900/75 border-t border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-3">
        <div class="w-full mx-auto flex justify-center">
            @yield('save')
        </div>
    </div>
@endif
```

## Backward Compatibility

All components support both snake_case (`confirm_label`) and camelCase (`confirmLabel`) for backward compatibility.

```blade
<!-- Both work -->
<x-admin.save-button-vanilla
    :confirm_label="__('common.save')"  {{-- snake_case --}}
/>

<x-admin.save-button-vanilla
    :confirmLabel="__('common.save')"   {{-- camelCase (recommended) --}}
/>
```

## Migration Guide

### Migrating from Alpine.js to Vanilla JS Version

1. Change the component name
2. Unify parameter names (recommended)

```diff
- <x-admin.save-button
+ <x-admin.save-button-vanilla
-     id_confirmation="confirmationModal"
+     modalId="confirmationModal"
-     :confirm_label="__('common.save')"
+     :confirmLabel="__('common.save')"
-     :cancel_label="__('common.cancel')"
+     :cancelLabel="__('common.cancel')"
      form="settings-form"
  />
```

## Troubleshooting

### Modal Does Not Open (Vanilla JS Version)

Verify that `window.modalManager` is initialized.

```javascript
console.log(window.modalManager); // Should display a ModalManager instance
```

### Form Does Not Submit

Ensure the `form` parameter is set to the correct form ID.

```blade
<form id="my-form" method="POST" action="...">
    ...
</form>

<x-admin.save-button-vanilla
    form="my-form"  {{-- Must match the form ID --}}
/>
```

## Summary

- **New development**: Use the Vanilla JS version
- **Existing pages**: Can continue using the Alpine.js version
- **Strict CSP mode**: the Vanilla JS version is required
- **Usage**: Simply place any version inside `@section('save')`
