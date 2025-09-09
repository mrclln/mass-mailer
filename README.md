# Mass Mailer Package

> **⚠️ UNDER DEVELOPMENT** - This package is currently under active development. Features and API may change.

A reusable Laravel Livewire package for mass emailing with attachments, templates, and queueing support.

## Features

- 📧 **Mass email sending** with personalization
- 📎 **Attachment support** (global and per-recipient)
- 🎨 **Rich text editor** (Quill.js v2.0.0) with drag-and-drop variables
- 📊 **CSV import** for recipients with automatic column mapping
- 🔄 **Queue-based processing** with configurable batching
- ⚙️ **Fully configurable** via config files and environment variables
- 📝 **Comprehensive logging** with database tracking
- 🎯 **Livewire integration** with real-time updates
- 🎨 **Multi-framework UI** (Bootstrap 5 & Tailwind CSS)
- 🖱️ **Drag & drop variables** directly into email content
- 📱 **Responsive design** that works on all devices
- 🔒 **Security features** with file validation and rate limiting

## Installation

### Step 1: Install via Composer

```bash
composer require mrclln/mass-mailer
```

### Step 2: Publish Configuration (Required)

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-config"
```

### Step 3: Configure UI Framework (Optional)

Choose your preferred UI framework by setting the environment variable:

```bash
# For Bootstrap (default)
echo "MASS_MAILER_UI_FRAMEWORK=bootstrap" >> .env

# For Tailwind CSS
echo "MASS_MAILER_UI_FRAMEWORK=tailwind" >> .env
```

Or update `config/mass-mailer.php`:
```php
'ui' => [
    'framework' => 'tailwind', // or 'bootstrap'
    // ... other options
],
```

### Step 4: Publish Views (Optional)

If you want to customize the views:

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-views"
```

### Step 5: Run Migrations (Optional, for logging)

If you want to enable email logging:

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-migrations"
php artisan migrate
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-config"
```

Publish the views (optional):

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-views"
```

Run the migrations (optional, for logging):

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-migrations"
php artisan migrate
```

## Usage

### Basic Usage

Add the component to your Blade template:

```blade
@livewire('mass-mailer')
```

Or use the HTML syntax:

```html
<livewire:mass-mailer />
```

### Drag & Drop Variables

The package includes an intuitive drag-and-drop system for inserting variables into your email content:

1. **Add Variables**: Use the input field to add custom variables
2. **Drag to Editor**: Click and drag variable names from the left panel
3. **Drop in Content**: Drop them directly into the Quill editor
4. **Auto-Formatting**: Variables are automatically formatted as `{{ variable_name }}`

**Supported Variables:**
- `{{ email }}` - Recipient email address
- `{{ first_name }}` - Recipient first name
- `{{ last_name }}` - Recipient last name
- Custom variables you create

**How to Use:**
1. Add variables using the "Add" button in the Variables panel
2. Click and hold on any variable name
3. Drag it to the desired location in the email editor
4. Release to insert the variable
5. The variable will appear as `{{ variable_name }}` in your email content

### Configuration

The package is highly configurable. Here are the main configuration options in `config/mass-mailer.php`:

```php
return [
    'enabled' => env('MASS_MAILER_ENABLED', true),

    // UI Framework Configuration
    'ui' => [
        'framework' => env('MASS_MAILER_UI_FRAMEWORK', 'bootstrap'), // 'bootstrap' or 'tailwind'
        'theme' => env('MASS_MAILER_UI_THEME', 'default'),
        'variables' => ['email', 'first_name', 'last_name'],
        'editor' => [
            'type' => env('MASS_MAILER_EDITOR_TYPE', 'quill'),
        ],
    ],

    // Queue Configuration
    'queue' => [
        'connection' => env('MASS_MAILER_QUEUE_CONNECTION', 'database'),
        'name' => env('MASS_MAILER_QUEUE_NAME', 'mass-mailer'),
    ],

    // Processing Configuration
    'batch_size' => env('MASS_MAILER_BATCH_SIZE', 50),
    'rate_limiting' => [
        'enabled' => env('MASS_MAILER_RATE_LIMITING_ENABLED', true),
        'max_per_minute' => env('MASS_MAILER_MAX_PER_MINUTE', 100),
    ],

    // Attachment Configuration
    'attachments' => [
        'max_size' => env('MASS_MAILER_MAX_ATTACHMENT_SIZE', 10240), // KB
        'allowed_types' => ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'],
        'storage_disk' => env('MASS_MAILER_STORAGE_DISK', 'public'),
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => env('MASS_MAILER_LOGGING_ENABLED', true),
        'table' => env('MASS_MAILER_LOG_TABLE', 'mass_mailer_logs'),
    ],
];
```

### UI Framework Configuration

The package supports both **Bootstrap** and **Tailwind CSS** frameworks:

#### Using Bootstrap (Default)
```php
'ui' => [
    'framework' => 'bootstrap', // or set MASS_MAILER_UI_FRAMEWORK=bootstrap
    // ... other options
],
```

#### Using Tailwind CSS
```php
'ui' => [
    'framework' => 'tailwind', // or set MASS_MAILER_UI_FRAMEWORK=tailwind
    // ... other options
],
```

**Note:** When using Tailwind CSS, make sure you have Tailwind CSS installed and configured in your project. The package will automatically use the appropriate CSS classes for each framework.

#### Tailwind CSS Features (Enhanced)
The Tailwind version includes additional modern features:

- 🎨 **Gradient backgrounds** with beautiful color schemes
- ✨ **Smooth hover animations** and transitions
- 📱 **Enhanced mobile responsiveness**
- 🎯 **Better visual hierarchy** with improved typography
- 🌈 **Modern color palette** with blue-to-purple gradients
- 🎪 **Interactive elements** with hover effects
- 📊 **Dynamic recipient counter** showing live updates
- 🎨 **Professional card layouts** with shadows and borders

## Features

### Personalization

Use variables in your email content that will be replaced with recipient data:

```
Dear {{ first_name }},

Thank you for your interest in {{ product_name }}.

Best regards,
{{ sender_name }}
```

### CSV Import

Upload a CSV file with recipient data. The first row should contain column headers that will become variables.

Example CSV:
```csv
email,first_name,last_name,company
john@example.com,John,Doe,Acme Corp
jane@example.com,Jane,Smith,Tech Inc
```

### Attachments

- **Global attachments**: Same files sent to all recipients
- **Per-recipient attachments**: Different files for each recipient

### Queue Processing

Emails are processed in the background using Laravel's queue system. Configure your queue connection in the config file.

### Batching

Large recipient lists are automatically split into batches for efficient processing.

### Logging

All email sending activities can be logged to a database table for tracking and analysis.

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+
- Livewire 3.0+
- Database (for logging, optional)

## Dependencies

- `laravel/framework` (^10.0|^11.0|^12.0)
- `livewire/livewire` (^3.0)
- `jantinnerezo/livewire-alert` (^4.0)

## External Dependencies

The package uses the following CDN resources (automatically loaded):

- **Quill.js** (v2.0.0) - Rich text editor
  - CSS: `https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css`
  - JS: `https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js`
- **PapaParse** (v5.4.1) - CSV parser
  - JS: `https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js`
- **FontAwesome** (v6.4.0) - Icons and UI enhancement
  - CSS: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css`

## Security

- All file uploads are validated
- Attachments are stored securely
- Rate limiting prevents abuse
- Comprehensive error handling

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the MIT license.

## Support

For support, please create an issue on GitHub or contact the maintainer.
