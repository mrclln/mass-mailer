# Attachment & CC Auto-Detection Feature

## Overview

The Mass Mailer package now includes automatic detection and processing of email attachments and CC recipients from CSV data. When a CSV file contains "attachments" and/or "cc" columns, the system will automatically detect file paths and email addresses and apply them to the corresponding recipient's email.

## Features

### ✅ Automatic Detection
- Automatically detects "attachments" column in CSV files
- Automatically detects "cc" column in CSV files
- Case-insensitive column name matching
- Seamless integration with existing CSV parsing

### ✅ Comma-Separated File Paths
- Supports multiple file attachments per recipient
- Files separated by commas (`,`)
- Automatic parsing and validation of each file path

### ✅ Comma-Separated CC Emails
- Supports multiple CC recipients per email
- Email addresses separated by commas (`,`)
- Automatic validation of email format
- Invalid email addresses are skipped with warnings

### ✅ File Validation
- Validates file existence before processing
- Checks file size against configured limits
- Logs warning messages for missing or oversized files

### ✅ Email Validation
- Validates email format using PHP filter_var()
- Logs warnings for invalid email addresses
- Continues processing with valid emails only

### ✅ Security & Safety
- Auto-detected files are **not deleted** after sending (unlike uploaded files)
- Only existing files on disk are processed
- MIME type detection for proper email attachment handling
- CC emails are never modified or stored permanently

## CSV Format

### Required Columns
Add `attachments` and/or `cc` columns to your CSV file with the following format:

```csv
email,first_name,last_name,attachments,cc
john.doe@example.com,John,Doe,/path/to/document.pdf,manager@example.com
jane.smith@example.com,Jane,Smith,/path/to/doc1.pdf,/path/to/doc2.txt,"team.lead@example.com,supervisor@example.com"
bob.wilson@example.com,Bob,Wilson,/path/to/image.jpg,
alice@example.com,Alice,,hr@example.com
```

### File Path Formats (attachments column)
- **Absolute paths**: `C:/Users/John/Documents/file.pdf`
- **Relative paths**: `documents/file.pdf`
- **Multiple files**: Separate with commas: `file1.pdf,file2.txt,image.jpg`
- **Empty values**: Leave blank for recipients without attachments

### Email Address Formats (cc column)
- **Single email**: `manager@example.com`
- **Multiple emails**: Separate with commas: `"email1@example.com,email2@example.com"`
- **Quoted values**: Use quotes for multiple emails with spaces: `"manager@example.com, supervisor@example.com"`
- **Empty values**: Leave blank for recipients without CC emails
- **Invalid emails**: Are automatically skipped with warnings

## Usage Examples

### Example 1: Single Attachment and CC
```csv
email,first_name,last_name,attachments,cc
john@example.com,John,Doe,C:/Documents/report.pdf,manager@example.com
```

### Example 2: Multiple Attachments and CCs
```csv
email,first_name,last_name,attachments,cc
jane@example.com,Jane,Smith,"C:/Docs/report.pdf,C:/Docs/spreadsheet.xlsx,C:/Images/chart.png","team.lead@example.com,supervisor@example.com"
```

### Example 3: Mixed Recipients with Both Features
```csv
email,first_name,last_name,attachments,cc
john@example.com,John,Doe,report.pdf,manager@example.com
jane@example.com,Jane,Smith,,
bob@example.com,Bob,Wilson,"doc1.pdf,doc2.pdf,image.jpg","admin@example.com,security@example.com"
alice@example.com,Alice,Johnson,,hr@example.com
charlie@example.com,Charlie,Brown,report.pdf,
```

## How It Works

### 1. CSV Parsing
- When a CSV file is uploaded, the system checks for an "attachments" column
- If found, it processes file paths in that column for each recipient
- Comma-separated values are split into individual file paths

### 2. File Processing
- Each file path is validated for existence
- File size is checked against configuration limits
- MIME type is automatically detected
- Files are prepared for email attachment

### 3. Email Sending
- Auto-detected attachments are added to recipient's email
- Works alongside existing manual attachment upload feature
- Auto-detected files are marked to prevent deletion after sending

## Configuration

### File Size Limits
Configure maximum file size in `config/mass-mailer.php`:

```php
'attachments' => [
    'max_size' => 10240, // KB (default: 10MB)
    'storage_disk' => 'public',
],
```

### Logging
Enable detailed logging in `config/mass-mailer.php`:

```php
'logging' => [
    'enabled' => true,
],
```

## Implementation Details

### Modified Files

1. **`src/Livewire/MassMailer.php`**
   - Enhanced `handleCSV()` method with attachment detection
   - Added `processAttachmentPaths()` helper method
   - Updated `sendMassMail()` to handle auto-detected attachments

2. **`src/Jobs/SendMassMailJob.php`**
   - Updated `cleanupAttachments()` to preserve auto-detected files
   - Enhanced logging for auto-detected attachments

### Key Changes

#### CSV Processing Enhancement
```php
// Auto-detect attachments column
$hasAttachmentsColumn = in_array('attachments', $cleanHeaders);
if ($hasAttachmentsColumn) {
    // Process attachment paths
    $filePaths = array_map('trim', explode(',', $value));
    $attachmentPaths = $this->processAttachmentPaths($filePaths, $i);
}
```

#### Attachment Processing
```php
// Handle auto-detected attachments from CSV
if (isset($recipient['_auto_attachments']) && is_array($recipient['_auto_attachments'])) {
    foreach ($recipient['_auto_attachments'] as $autoAttachment) {
        $recipientAttachments[] = [
            'path' => $autoAttachment['path'],
            'name' => $autoAttachment['name'],
            'mime' => $autoAttachment['mime'],
            'auto_detected' => true
        ];
    }
}
```

#### Safe Cleanup
```php
// Skip deletion for auto-detected files
if (!($attachment['auto_detected'] ?? false)) {
    unlink($attachment['path']); // Only delete uploaded files
}
```

## Testing

Use the included test scripts to verify functionality:

### Test Attachment Detection
```bash
php test-attachment-detection.php
```

### Test CC Email Detection
```bash
php test-cc-functionality.php
```

### Test Files Included
- `test-attachments/test-contacts.csv` - Sample CSV with attachment data
- `test-attachments/test-contacts-with-cc.csv` - Sample CSV with both attachments and CC data
- `test-attachments/sample-document.txt` - Test attachment file
- `test-attachments/readme.md` - Additional test file

## Benefits

### Attachment Benefits
1. **Time Saving**: No need to manually upload files for each recipient
2. **Scalability**: Handle large numbers of attachments efficiently
3. **Flexibility**: Mix manual uploads with auto-detected files
4. **Reliability**: Robust validation and error handling
5. **Integration**: Works seamlessly with existing mass mailer features

### CC Email Benefits
1. **Automated CC Management**: No need to manually add CC recipients
2. **Flexible Targeting**: Different CC recipients for different main recipients
3. **Validation**: Automatic email format validation
4. **Multiple Recipients**: Support for multiple CC emails per recipient
5. **Error Handling**: Invalid emails are skipped with warnings, processing continues

## Error Handling

The system provides comprehensive error handling:

- **Missing Files**: Logs warnings and continues processing
- **File Size Limits**: Validates against configured limits
- **Invalid Paths**: Skips invalid file paths with logging
- **MIME Detection**: Automatic content type detection

## Best Practices

1. **Use Absolute Paths**: Provide complete file paths for reliability
2. **Check File Permissions**: Ensure files are accessible to the application
3. **Validate File Sizes**: Keep files within configured size limits
4. **Use Meaningful Names**: Include file extensions for proper MIME detection
5. **Test with Sample Data**: Use the provided test script to verify setup

## Migration Guide

### For Existing Users
- No changes required for existing functionality
- New feature is completely backward compatible
- CSV files without "attachments" column work as before
- Manual attachment uploads continue to work normally

### Enabling the Feature
1. Add "attachments" column to CSV files
2. Populate with file paths (comma-separated for multiple files)
3. Upload CSV and send emails as normal
4. Check logs for attachment processing details

## Support

For issues or questions:
1. Check the application logs for detailed error messages
2. Verify file paths and permissions
3. Test with the provided test script
4. Review configuration settings in `config/mass-mailer.php`
