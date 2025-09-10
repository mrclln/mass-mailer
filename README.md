# Mass Mailer Package

A powerful and user-friendly Laravel package for sending personalized mass emails with advanced features and professional-grade functionality.

## 🚀 Powerful Features

This package delivers enterprise-level email marketing capabilities with an intuitive interface:

- **📧 Smart Personalization** - Send truly personalized emails using dynamic variables
- **📎 Flexible Attachments** - Support for both global and per-recipient file attachments
- **✨ Rich Text Editor** - Professional Quill.js editor with formatting options
- **📊 CSV Import** - Seamless recipient import with automatic data mapping
- **⚡ Background Processing** - Queue-based sending for optimal performance
- **📈 Comprehensive Logging** - Full tracking and analytics of email campaigns
- **🎨 Multi-Framework UI** - Beautiful interfaces for Bootstrap 5 and Tailwind CSS
- **🖱️ Drag & Drop Variables** - Intuitive variable insertion system
- **📱 Fully Responsive** - Perfect experience on all devices and screen sizes
- **🔒 Enterprise Security** - File validation, rate limiting, and secure storage

## 📸 Screenshots

### Main Interface
*Beautiful, clean interface with intuitive layout*
![Main Interface](screenshots/main-interface.png)

### Variable Management
*Easy-to-use variable system for personalization*
![Variable Management](screenshots/variable-management.png)

### Rich Text Editor
*Professional email composition with formatting tools*
![Rich Text Editor](screenshots/rich-text-editor.png)

### CSV Import
*Seamless recipient import with data preview*
![CSV Import](screenshots/csv-import.png)

### Attachment Management
*Flexible attachment options for different use cases*
![Attachment Management](screenshots/attachment-management.png)

### Email Preview
*Real-time preview of personalized emails*
![Email Preview](screenshots/email-preview.png)

### Bootstrap Theme
*Clean Bootstrap 5 interface*
![Bootstrap Theme](screenshots/bootstrap-theme.png)

### Tailwind Theme
*Modern Tailwind CSS design*
![Tailwind Theme](screenshots/tailwind-theme.png)

## 🚀 Quick Start

Getting started is incredibly simple - just follow these steps and you'll be sending professional mass emails in minutes!

### 1. ⚡ Install the Package

Add this powerful package to your Laravel project:

```bash
composer require mrclln/mass-mailer
```

### 2. 🔧 Configure Your Setup

Publish the configuration to unlock all customization options:

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-config"
```

### 3. 🎨 Choose Your Perfect UI

Select the framework that matches your project's design:

**Bootstrap 5 (Default)**
```bash
# Add to your .env file
MASS_MAILER_UI_FRAMEWORK=bootstrap
```

**Tailwind CSS**
```bash
# Add to your .env file
MASS_MAILER_UI_FRAMEWORK=tailwind
```

### 4. 📊 Enable Advanced Logging

Track every email sent with comprehensive analytics:

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-migrations"
php artisan migrate
```

### 5. 🎯 Customize to Your Needs

Make it truly yours by publishing the views:

```bash
php artisan vendor:publish --provider="Mrclln\MassMailer\Providers\MassMailerServiceProvider" --tag="mass-mailer-views"
```

## 🎯 How to Use

### 🚀 Basic Usage

Integrate the mass mailer into any page with just one line:

```blade
@livewire('mass-mailer')
```

Or use the HTML syntax:

```html
<livewire:mass-mailer />
```

That's all it takes! You'll get a complete, professional email marketing interface ready to use.

### 🎨 Advanced Variable System

Our intelligent variable system transforms generic emails into personalized conversations that drive engagement and boost response rates.

#### ✨ Effortless Variable Creation

Create custom variables instantly with our intuitive interface:

1. Navigate to the powerful "Variables" panel on the left
2. Enter your variable name (e.g., "company", "product_name", "account_manager")
3. Click "Add" - it's that simple!
4. Your variable appears in the dynamic, interactive list

#### 🚀 Powerful Personalization Engine

Harness the full power of dynamic content:

- `{{ email }}` - Recipient's email address for complete personalization
- `{{ first_name }}` - Personal greeting capability that builds connections
- `{{ last_name }}` - Complete personalization for professional communications
- **Any custom variables** you create for your specific business needs

**Example of highly personalized email:**
```
Dear {{ first_name }},

As a valued {{ company }} customer, we're excited to offer you
exclusive access to {{ product_name }} with special pricing.

Your dedicated account manager, {{ account_manager }},
will be reaching out within 24 hours.

Best regards,
{{ sender_name }}
The {{ company_name }} Team
```

#### 🖱️ Revolutionary Drag & Drop Technology

Experience our innovative drag-and-drop variable insertion system:

- **Click and drag** any variable from the interactive list
- **Drop directly** into your email content with precision
- **Automatic formatting** with proper `{{ }}` syntax
- **Real-time preview** of personalized content as you compose

This cutting-edge feature makes personalization effortless and professional!

### ⚙️ Advanced Configuration Engine

Our comprehensive configuration system gives you complete control over every aspect of your email campaigns, with sensible defaults that work out of the box.

#### 🎛️ Core Configuration Options

**System Control:**
- **Enable/Disable**: Complete control over package activation
- **UI Framework**: Choose between Bootstrap 5 or Tailwind CSS seamlessly
- **Default Variables**: Pre-configure common personalization variables

**Performance Optimization:**
- **Queue Settings**: Fine-tune background processing for optimal performance
- **Batch Size**: Intelligent batching (default: 50) for maximum efficiency
- **Rate Limiting**: Advanced throttling to prevent system overload

**File Management:**
- **File Size Limits**: Granular control over attachment sizes
- **File Type Restrictions**: Comprehensive security through type validation
- **Storage Configuration**: Flexible storage options for your infrastructure

**Analytics & Tracking:**
- **Comprehensive Logging**: Full campaign analytics and tracking
- **Custom Database Tables**: Adapt to your existing database schema

#### 🎨 Premium UI Framework Support

**Bootstrap 5 (Default)**
- Professional Bootstrap 5 components with modern styling
- Responsive grid system that adapts to any screen size
- Seamless integration with existing Bootstrap projects
- Enterprise-grade component library

**Tailwind CSS**
- Utility-first approach for maximum customization
- Modern design system with consistent spacing and colors
- Complete design freedom for custom branding
- Optimized for performance and maintainability

**Framework Switching:**
Change frameworks instantly with a single environment variable:
```bash
MASS_MAILER_UI_FRAMEWORK=tailwind  # or bootstrap
```

Our intelligent system automatically adapts all components, maintaining full functionality across frameworks.

### 🌟 Enterprise-Grade Features

#### 🎯 Intelligent Personalization Engine

Transform generic emails into highly targeted, personalized communications that drive engagement and conversions:

```
Dear {{ first_name }},

We noticed your interest in {{ product_name }} at {{ company }}.
Would you like to learn more about our premium {{ service_type }} solutions
tailored specifically for {{ industry }} businesses?

Your dedicated success manager, {{ account_manager }},
will be reaching out within 24 hours.

Best regards,
{{ sender_name }}
{{ company_name }} Team
```

Our advanced variable system ensures every email feels like a personal conversation.

#### 📊 Professional CSV Import System

Seamlessly import and manage large recipient databases with our intelligent import engine:

- **Automatic Column Detection**: Smart header recognition and mapping
- **Dynamic Variable Creation**: Instant variable generation from CSV headers
- **Data Validation**: Comprehensive validation with error reporting
- **Bulk Editing**: Edit and refine data before sending
- **Progress Tracking**: Real-time import status and statistics

**Example Professional CSV:**
```csv
email,first_name,last_name,company,industry,account_manager
john@example.com,John,Doe,Acme Corp,Technology,Sarah Johnson
jane@example.com,Jane,Smith,Tech Inc,Healthcare,Mike Chen
```

#### 📎 Advanced Attachment Management

Our flexible attachment system supports complex document distribution scenarios:

**Global Attachments**
- Single upload, universal distribution
- Perfect for company policies, brochures, and standard documents
- Bandwidth optimized with smart caching

**Per-Recipient Attachments**
- Individualized document delivery
- Custom contracts, certificates, and personalized materials
- Granular permission and access control
- Secure, encrypted file handling

#### ⚡ High-Performance Background Processing

Enterprise-grade queue processing ensures optimal performance and reliability:

- **Laravel Queue Integration**: Native queue system compatibility
- **Zero Downtime**: Users never wait for email processing
- **Scalable Architecture**: Handles thousands of emails effortlessly
- **Real-Time Monitoring**: Live progress tracking and status updates

#### 🧠 Smart Batching Technology

Our intelligent batching system optimizes email delivery for maximum success:

- **Dynamic Batch Sizing**: Automatically adjusts based on system capacity
- **Timeout Prevention**: Eliminates processing timeouts on large campaigns
- **Resource Optimization**: Balances server load for consistent performance
- **Delivery Analytics**: Comprehensive batch performance metrics

#### 📈 Comprehensive Campaign Analytics

Complete visibility into your email marketing performance:

- **Real-Time Tracking**: Live delivery status and open rates
- **Detailed Logging**: Complete audit trail of all email activities
- **Error Analysis**: Advanced diagnostics for failed deliveries
- **Performance Metrics**: Campaign success rates and engagement data
- **Export Capabilities**: Data export for external analysis tools

### 🏗️ Robust System Architecture

**Production-Ready Requirements:**
- **PHP**: 8.1+ for optimal performance and security
- **Laravel**: 10.0, 11.0, or 12.0 with full framework compatibility
- **Livewire**: 3.0+ for reactive, modern user experiences
- **Database**: Enterprise-grade database support (optional for logging)

### 🛠️ Professional Dependencies

Built on battle-tested Laravel ecosystem components:

- **Laravel Framework**: Rock-solid foundation with enterprise features
- **Livewire 3.0**: Cutting-edge reactive components and real-time updates
- **Livewire Alert**: Professional notification system with beautiful UX

### 🌐 Optimized External Resources

Carefully selected, high-performance external libraries:

- **Quill.js v1.3.6**: Industry-leading rich text editor with extensive formatting
- **PapaParse v5.4.1**: Lightning-fast CSV processing with robust error handling
- **FontAwesome v6.4.0**: Comprehensive icon library for professional interfaces

All resources loaded via CDN for optimal performance and zero installation overhead.

### 🔒 Enterprise-Grade Security

Comprehensive security architecture protecting your email campaigns:

- **Advanced File Validation**: Multi-layer security checks on all uploads
- **Type & Size Restrictions**: Granular control over file specifications
- **Rate Limiting**: Intelligent throttling to prevent system abuse
- **Secure Storage**: Encrypted file handling with configurable storage backends
- **Error Resilience**: Graceful failure handling and comprehensive logging

### 🤝 Join Our Community

We're proud of our growing community and welcome contributions from developers worldwide!

**How to Contribute:**
1. **Fork** our repository and clone your copy
2. **Create** a feature branch for your amazing improvements
3. **Develop** with our comprehensive testing suite
4. **Test** thoroughly across different Laravel versions
5. **Submit** a pull request with detailed documentation

Every contribution strengthens our package and helps thousands of developers worldwide.

### 📄 Professional License

Released under the **MIT License** - the industry standard for open-source software. This gives you:

- ✅ **Complete Freedom**: Use in commercial and personal projects
- ✅ **No Licensing Fees**: Zero cost for production deployment
- ✅ **Modification Rights**: Customize to fit your exact needs
- ✅ **Redistribution**: Share with your team or clients

### 🆘 Expert Support

Our dedicated support system ensures you never get stuck:

**📚 Comprehensive Documentation**
- Detailed setup guides and configuration options
- Real-world examples and use cases
- Troubleshooting guides for common issues

**💬 Community Support**
- Active GitHub issue tracking and resolution
- Community forum for peer-to-peer assistance
- Regular updates and feature enhancements

**🎯 Our Mission**
To provide the most powerful, user-friendly mass email solution that empowers developers to create exceptional email experiences for their users.
