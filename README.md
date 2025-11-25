# Mass Mailer

A comprehensive Laravel package for mass email campaigns with advanced logging, analytics, and Livewire components. Features a clean interface that works with both Bootstrap and Tailwind CSS frameworks.

## ✨ Features

### Core Email Functionality

- **🎯 Smart Personalization** - Use variables like `{{ first_name }}`, `{{ email }}` for personalized content
- **📊 CSV Import** - Upload recipient lists with automatic data mapping
- **📎 Flexible Attachments** - Global attachments for all recipients or individual ones per person
- **✨ Rich Text Editor** - Compose emails with formatting using Quill.js
- **👥 Multiple Senders** - Switch between different email accounts/senders
- **👀 Email Preview** - See exactly how your email will look before sending
- **⚡ Background Processing** - Queue-based sending for better performance
- **🔒 SMTP Validation** - Test email credentials before saving sender profiles
- **🎨 Dual Framework Support** - Choose between Bootstrap 5 or Tailwind CSS
- **📱 Fully Responsive** - Works perfectly on all devices
- **🔐 Secure & Configurable** - File validation, rate limiting, and customizable settings

### Advanced Logging & Analytics (v2.0.0)

- **📈 MassMailerLogs Component** - Comprehensive log management interface
- **🔍 Advanced Filtering** - Search by recipient, subject, error messages, status, and date ranges
- **📊 Dashboard Statistics** - Real-time metrics and success rate tracking
- **📤 Export Functionality** - CSV and JSON export with filtered data support
- **🔄 Retry Mechanism** - Retry failed emails directly from the interface
- **👁️ Log Details View** - Comprehensive view of individual email logs
- **🗑️ Data Management** - Clear old logs with confirmation dialogs
- **📋 Pagination** - Efficient browsing of large log datasets

### User Analytics & Integration (v2.0.0)

- **👤 MassMailerUserTrait** - Comprehensive trait for User model integration
- **📊 30+ Analytics Methods** - Email analytics and reporting for users
- **⏰ Time-based Analytics** - Daily, weekly, monthly, and yearly reporting
- **📈 Performance Tracking** - Success rates, failure analysis, recipient tracking
- **💾 Data Export** - CSV and array export capabilities for user email logs
- **🔗 Relationship Methods** - Direct relationships for logs and senders
- **🎯 Advanced Reporting** - Performance trends, subject performance analysis
- **🔒 User Data Isolation** - Built-in security for multi-user environments

### 🏗️ Service Architecture (v2.1.0)

- **🔧 Clean Code Structure** - Refactored with dedicated service classes for better maintainability
- **📦 Service-Based Design** - Separate services for attachments, CSV processing, senders, recipients, and email templates
- **🧪 Enhanced Testability** - Dependency injection and service container usage for better testing
- **⚡ Improved Performance** - Optimized code structure with reduced file size
- **🔄 Better Maintainability** - Changes localized to specific services

### 🚀 Modern File Upload (v2.2.0)

- **💫 FilePond Integration** - Modern, drag-and-drop file upload interface
- **🎨 Visual File Management** - Beautiful file upload with image previews and progress indicators
- **📁 Drag & Drop Support** - Users can drag files directly onto upload areas
- **🖼️ Image Preview** - Automatic preview for uploaded image files
- **📊 Upload Progress** - Real-time progress indicators during file uploads
- **✅ File Type Validation** - Built-in file type and size validation
- **🔄 Multiple File Selection** - Easy management of multiple files
- **⚡ Livewire Compatible** - Seamless integration with existing Livewire file handling

## 📁 Project Structure

```
laravel-mass-mailer/
├── src/                          # Main package source code
│   ├── Services/                 # Service architecture (v2.1.0)
│   │   ├── AttachmentService.php # File attachment handling
│   │   ├── CsvService.php        # CSV parsing and processing
│   │   ├── SenderService.php     # Sender management & validation
│   │   ├── RecipientService.php  # Recipient management
│   │   └── EmailTemplateService.php # Email template processing
│   ├── Livewire/                 # Livewire components
│   ├── Views/                    # Blade templates
│   ├── Models/                   # Eloquent models
│   ├── Jobs/                     # Queue jobs
│   ├── Config/                   # Configuration files
│   └── Traits/                   # User integration traits
├── docs/                         # Documentation and examples
│   ├── examples/                 # Test and example scripts
│   └── assets/                   # Images and test data
├── tests/                        # PHPUnit test suite
└── composer.json                 # Package dependencies
```

## 📸 Screenshots

### Bootstrap Version

![Bootstrap Interface](docs/assets/bootstrap.png)

### Tailwind Version

![Tailwind Interface](docs/assets/tailwind.png)

### Mass Mailer Logs Interface (v2.0.0)

![Mass Mailer Logs Interface](docs/assets/mass-mailer-logs.png)

## 🚀 Quick Start

```bash
composer require mrclln/mass-mailer
```

### Basic Setup

1. **Install the package**

```bash
composer require mrclln/mass-mailer
```

2. **Publish configuration** (optional, for customization)

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-config"
```

3. **Database Setup** (recommended, for logging and analytics)

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-migrations"
php artisan migrate
```

**What this enables:**

- Complete email logging and tracking
- User analytics and statistics
- Advanced filtering and search capabilities
- Export functionality for email logs
- Retry mechanism for failed emails
- Performance monitoring and reporting
  If the provider is **not automatically added**, open:
  bootstrap/providers.php
  and add:

```bash
return [
    // Other providers...
    Mrclln\MassMailer\Providers\MassMailerServiceProvider::class,
];
```

4. **Choose your UI style** in `.env`

```bash
MASS_MAILER_UI_FRAMEWORK=bootstrap  # or 'tailwind'
```

## 🎯 How to Use

Add to any Blade view:

```blade
@livewire('mass-mailer')
```

Or use the component syntax:

```html
<livewire:mass-mailer />
```

### MassMailerLogs Component (v2.0.0)

View and manage all your email campaigns with the comprehensive logs interface:

```blade
@livewire('mass-mailer-logs')
```

**Features:**

- **Real-time Dashboard** - View statistics, success rates, and performance metrics
- **Advanced Search & Filtering** - Filter by recipient, subject, status, date ranges
- **Export Capabilities** - Download logs as CSV or JSON files
- **Detailed Log View** - Click any log to see full email details
- **Retry Failed Emails** - Directly retry failed emails from the interface
- **Data Management** - Clear old logs with confirmation dialogs
- **Responsive Design** - Works on all devices with mobile-first approach

### MassMailerUserTrait (v2.0.0)

Add comprehensive email analytics to your User model:

```php
// app/Models/User.php
use Mrclln\MassMailer\Traits\MassMailerUserTrait;

class User extends Authenticatable
{
    use MassMailerUserTrait;

    // Your existing User code...
}
```

**Available Methods:**

- **Statistics**: `getMassMailerStats()`, `getMassMailerSuccessRate()`
- **Time Analytics**: `getMassMailerEmailsSentToday()`, `getMassMailerEmailsSentThisWeek()`
- **Performance**: `getMassMailerSuccessRate()`, `getMassMailerFailedEmails()`
- **Relationships**: `massMailerLogs()`, `massMailerSenders()`, `successfulMassMailerLogs()`
- **Export**: `exportMassMailerLogsToCsv()`, `exportMassMailerLogsToArray()`
- **Analysis**: `getMassMailerFailureAnalysis()`, `getMassMailerRecipientTracking()`

**Example Usage:**

```php
$user = auth()->user();

// Get user's email statistics
$stats = $user->getMassMailerStats();

// Get success rate
$successRate = $user->getMassMailerSuccessRate();

// Export user's email logs
$csvData = $user->exportMassMailerLogsToCsv();
```

## 🎨 UI Framework Support

The package automatically adapts to your chosen framework:

### Bootstrap 5

- Clean, professional interface
- Bootstrap components and styling
- Perfect for existing Bootstrap projects

### Tailwind CSS

- Modern utility-first design
- Consistent with Tailwind projects
- Highly customizable

Both versions have identical functionality and features.

## ⚙️ Key Features Explained

### Personalization with Variables

Create custom variables and drag them into your emails:

- `{{ first_name }}` - Recipient's first name
- `{{ email }}` - Their email address
- Any custom variables you create

### CSV Import

Upload recipient data easily:

```csv
email,first_name,last_name,company
john@example.com,John,Doe,Acme Corp
jane@example.com,Jane,Smith,Tech Inc
```

### Multiple Senders

Configure different email accounts and even attach senders to user models for dynamic sender management:

#### Option 1: Configuration-based Senders

```php
// In config/mass-mailer.php
'multiple_senders' => true,
'senders' => [
    ['name' => 'Support', 'email' => 'support@company.com'],
    ['name' => 'Sales', 'email' => 'sales@company.com'],
]
```

#### Option 2: Database-driven Senders (Recommended)

First, **run the migration** to create the senders table:

```bash
php artisan migrate
```

**New in v2.0.0: SMTP Validation**
When adding new sender profiles, the system automatically:

- Tests SMTP credentials before saving
- Sends a test email to validate the configuration
- Prevents saving invalid SMTP settings
- Provides clear error messages for failed validations

**Then create the relationship in your User model:**

```php
// app/Models/User.php
use Mrclln\MassMailer\Models\MassMailerSender;

class User extends Authenticatable
{
    // ... other code

    public function massMailerSenders()
    {
        return $this->hasMany(MassMailerSender::class);
    }
}
```

**Configure the package to use the User model:**

```php
// config/mass-mailer.php
'multiple_senders' => true,
'sender_model' => \App\Models\User::class,
```

**Benefits of User Model Attachment:**

- Each user can have their own sender profiles
- Perfect for multi-tenant applications
- Dynamic sender loading based on authenticated user
- Easy to manage sender permissions per user
- Supports complex business logic for sender selection
- New senders can be added through the UI interface

### Attachments

- **Global**: Same file for all recipients
- **Per-recipient**: Individual files for each person
- Supports PDF, DOC, images, and more

## 🔧 Configuration Options

Customize in `config/mass-mailer.php`:

- **Queue Settings**: Background processing configuration
- **Batch Size**: Emails per batch (default: 50)
- **Rate Limiting**: Max emails per minute
- **File Uploads**: Size limits and allowed file types
- **UI Framework**: Bootstrap or Tailwind
- **Multiple Senders**: Enable sender switching

## ⚡ Queue Worker (Important!)

Since the mass mailer uses Laravel's queue system for background processing, you **must** start the queue worker for emails to be sent:

```bash
php artisan queue:work --queue=mass-mailer
```

**For production**, consider using a process manager like Supervisor:

```bash
# Install Supervisor (Ubuntu/Debian)
sudo apt-get install supervisor

# Create configuration file
sudo nano /etc/supervisor/conf.d/mass-mailer-worker.conf
```

Add this configuration:

```ini
[program:mass-mailer-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --queue=mass-mailer --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

Then start the worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start mass-mailer-worker:*
```

## 🧪 Testing (v2.1.0)

The package includes comprehensive test files to verify functionality:

```bash
# Test basic functionality
php docs/examples/test-mass-mailer-trait.php

# Test email logging
php docs/examples/test-email-logging.php

# Test sender validation
php docs/examples/test-sender-validation.php

# Test CC functionality
php docs/examples/test-cc-functionality.php

# Test attachment detection
php docs/examples/test-attachment-detection.php
```

These test files help verify that all features work correctly in your environment.

## 📋 Requirements

- PHP 8.1+
- Laravel 10.0, 11.0, or 12.0
- Livewire 3.0+
- Database (recommended, for logging and analytics)

## 📚 Documentation

- **[MassMailerUserTrait Guide](docs/MASS_MAILER_USER_TRAIT_GUIDE.md)** - Complete guide for using the User trait
- **[MassMailerLogs Component Guide](docs/MASS_MAILER_LOGS_COMPONENT.md)** - Detailed documentation for the logs interface
- **[Package Publishing Guide](docs/PACKAGE_PUBLISHING_GUIDE.md)** - Information for contributors
- **[Attachment Auto Detection](docs/ATTACHMENT_AUTO_DETECTION.md)** - Attachment handling documentation

## 🧪 Examples

Example scripts and test files are available in `docs/examples/`:

- `test-mass-mailer-trait.php` - User trait testing
- `test-email-logging.php` - Email logging functionality
- `test-sender-validation.php` - SMTP validation testing
- `test-cc-functionality.php` - Carbon copy feature testing
- `test-attachment-detection.php` - Attachment handling tests
- `test-auto-file-upload.php` - File upload automation tests

## 🚀 What's New in v2.2.0

- **💫 FilePond Integration** - Modern, professional file upload interface with drag-and-drop
- **🎨 Enhanced User Experience** - Beautiful visual feedback with progress indicators and image previews
- **📁 Drag & Drop Support** - Users can easily drag files onto upload areas
- **🖼️ Image Preview Support** - Automatic thumbnail generation for image files
- **⚡ Real-time Progress** - Live upload progress indicators for better user feedback
- **🔄 Multiple File Management** - Easy selection and management of multiple files
- **✅ Smart Validation** - Built-in file type and size validation with visual feedback
- **📱 Mobile Friendly** - Optimized for mobile devices with touch-friendly interactions
- **🔗 Livewire Compatible** - Seamless integration with existing Livewire functionality

## 🚀 What's New in v2.1.0

- **🏗️ Service Architecture Refactoring** - Complete code restructuring with 5 dedicated service classes
- **📦 53% Code Reduction** - MassMailer.php reduced from 1,491 to 699 lines
- **🔧 Enhanced Maintainability** - Clean separation of concerns with service-based design
- **🧪 Improved Testability** - Dependency injection and service container usage
- **⚡ Better Performance** - Optimized code structure and reduced complexity
- **📚 Professional Structure** - Organized directory with docs/, examples/, and assets/ folders

## 🚀 What's New in v2.0.0

- **Complete Analytics Suite** - 30+ methods for user email analytics
- **Advanced Log Management** - Comprehensive logging with search, filter, and export
- **SMTP Validation** - Automatic testing of email credentials before saving
- **User Data Isolation** - Multi-user support with proper data separation
- **Enhanced UI** - Improved interfaces for both Bootstrap and Tailwind
- **Retry Mechanism** - Easy retry of failed emails from the logs interface
- **Performance Monitoring** - Real-time statistics and success rate tracking

---

Built by an individual developer sharing open source projects. This package helps make mass emailing simple and effective!
