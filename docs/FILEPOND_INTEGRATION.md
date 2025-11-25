# FilePond Integration for Mass Mailer

This document describes the integration of FilePond into the Mass Mailer package for enhanced file upload functionality.

## Overview

The Mass Mailer now uses FilePond for all file uploads, providing a modern, user-friendly file upload experience with features like:

- Drag and drop file uploads
- Image preview support
- Progress indicators
- File type validation
- Multiple file selection
- Better error handling

## Files Created/Modified

### New Files
- `src/Views/components/shared/filepond.blade.php` - FilePond component for Livewire integration

### Modified Files
- `src/Views/components/shared/external-libraries.blade.php` - Added FilePond CSS and JS
- `src/Views/tailwind/mass-mailer.blade.php` - Replaced file inputs with FilePond
- `src/Views/bootstrap/mass-mailer.blade.php` - Replaced file inputs with FilePond
- `src/Views/components/tailwind/attachment-modal.blade.php` - Replaced file input with FilePond
- `src/Views/components/bootstrap/attachment-modal.blade.php` - Replaced file input with FilePond

## Usage Examples

### Basic File Upload
```php
@include('mass-mailer::components.shared.filepond', [
    'wireModel' => 'csvFile',
    'accept' => '.csv,.txt',
    'multiple' => false,
])
```

### Multiple File Upload
```php
@include('mass-mailer::components.shared.filepond', [
    'wireModel' => 'globalAttachments',
    'multiple' => true,
])
```

### With Custom File Types
```php
@include('mass-mailer::components.shared.filepond', [
    'wireModel' => "perRecipientAttachments.{$selectedRecipientIndex}",
    'multiple' => true,
    'accept' => '.pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif',
])
```

## Component Props

- `wireModel` (required) - The Livewire property name for file binding
- `multiple` (boolean, default: false) - Allow multiple file selection
- `accept` (string, optional) - Comma-separated list of accepted file types
- `label` (string, optional) - Custom label for the file input
- `maxSize` (integer, optional) - Maximum file size in KB (uses config default if not specified)
- `allowImagePreview` (boolean, default: true) - Enable image preview for image files

## Features

### Livewire Integration
The component automatically handles:
- File uploads via `@this.upload()`
- File removal via `@this.removeUpload()`
- Progress tracking
- Error handling

### FilePond Configuration
- Multiple file support
- Image preview for image files
- File type validation
- Size limit enforcement
- Server-side integration with Livewire

### Automatic Loading
FilePond CSS and JS files are automatically loaded when needed via the `external-libraries.blade.php` component with the `filepond` library included.

## Benefits

1. **Better UX**: Modern drag-and-drop interface with visual feedback
2. **Multiple Files**: Easy selection and management of multiple files
3. **Preview**: Image preview for uploaded images
4. **Validation**: Built-in file type and size validation
5. **Progress**: Visual upload progress indicators
6. **Livewire Compatible**: Seamless integration with existing Livewire file handling

## Backward Compatibility

All existing Livewire file upload functionality remains unchanged. The FilePond component wraps the existing Livewire file upload behavior, ensuring that:
- File validation rules work as before
- File processing logic remains the same
- No changes required in controller or service classes

## Configuration

FilePond respects the existing configuration:
- Maximum file size from `config('mass-mailer.attachments.max_size')`
- All existing validation rules
- File processing and storage logic

## Dependencies

FilePond and its plugins are loaded from CDN:
- `https://unpkg.com/filepond/dist/filepond.css`
- `https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css`
- `https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js`
- `https://unpkg.com/filepond/dist/filepond.js`
