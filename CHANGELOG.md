# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.3] - 2025-11-24

### Fixed
- **Email Profile Switching Issue**: Fixed critical bug where switching between sender profiles (e.g., "DEV EMAIL" to "Allen") would only change the display name while the actual sender email remained from the previous profile
- **SMTP Configuration Caching**: Resolved configuration caching issues that prevented proper switching between different SMTP profiles
- **Sender Credentials Handling**: Improved sender credentials preparation to ensure both name and email are properly passed for all sender types (config-based and database)

### Enhanced
- **Logging**: Added comprehensive logging throughout the email sending process for better debugging
- **SMTP Configuration Management**: Enhanced configuration handling with proper cache clearing between sender switches
- **Error Handling**: Improved error handling and validation for sender credentials

### Technical Improvements
- Enhanced `selectSender()` method with better logging and user feedback
- Fixed sender credentials preparation for both config-based and database senders
- Improved SMTP configuration reset mechanism in background jobs
- Added detailed from address handling with proper fallbacks

### Files Modified
- `src/Livewire/MassMailer.php` - Enhanced sender selection and credential preparation
- `src/Jobs/SendMassMailJob.php` - Improved SMTP configuration and email sending
- `composer.json` - Version updated to 1.2.3

## [1.2.2] - Previous Release
- Previous stable release with mass mailer functionality
