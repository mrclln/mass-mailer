# Mass Mailer Logs Component - User Guide

The MassMailerLogs component provides a comprehensive interface for viewing, filtering, and managing email logs from your mass mailer campaigns. This component works with both Tailwind CSS and Bootstrap frameworks.

## 🚀 Quick Start

### 1. Include in Your Routes

```php
// routes/web.php
use Mrclln\MassMailer\Livewire\MassMailerLogs;

Route::get('/admin/email-logs', MassMailerLogs::class)->name('mass-mailer-logs');
```

### 2. Include in Your Layout

Make sure Livewire scripts are included in your main layout:

```blade
<!-- resources/views/layouts/app.blade.php -->
@livewireStyles
```

And before closing `</body>` tag:
```blade
@livewireScripts
```

### 3. Access the Component

Navigate to `/admin/email-logs` (or your custom route) to access the logs interface.

## 📊 Features

### **Dashboard Statistics**
- **Total Emails**: Complete count of all email attempts
- **Sent Emails**: Successfully delivered emails
- **Failed Emails**: Emails that failed to send
- **Success Rate**: Percentage of successful deliveries

### **Advanced Filtering**
- **Search**: Find logs by recipient email, subject, or error messages
- **Status Filter**: Filter by sent, failed, or pending status
- **Date Range**: Filter emails by custom date ranges
- **Real-time Updates**: Filters update results instantly

### **Log Management**
- **Pagination**: Browse large log datasets efficiently
- **Export**: Download logs in CSV or JSON format
- **Log Details**: View comprehensive information for each email
- **Retry Failed Emails**: Re-attempt sending failed emails
- **Clear Old Logs**: Remove logs older than 6 months

### **Data Export**
- **CSV Export**: Spreadsheet-compatible format
- **JSON Export**: Structured data format
- **Filtered Export**: Export only filtered results

## 🔧 Component Methods

### **Filtering Methods**
```php
// Search across email content
$search = 'john@example.com';

// Filter by delivery status
$statusFilter = 'sent'; // 'sent', 'failed', 'pending'

// Date range filtering
$dateFrom = '2024-01-01';
$dateTo = '2024-01-31';

// Items per page
$perPage = 25;
```

### **Action Methods**
```php
// View detailed log information
viewLogDetails($logId);

// Export filtered results
exportLogs();

// Clear old logs (older than 6 months)
clearOldLogs();

// Retry failed email
retryFailedEmail($logId);

// Clear all filters
clearFilters();

// Toggle filter visibility
toggleFilters();
```

## 🎨 UI Components

### **Statistics Cards**
Four dashboard cards showing key metrics:
- Total email count
- Successful deliveries
- Failed attempts
- Success rate percentage

### **Filter Panel**
Expandable section containing:
- Search input field
- Status dropdown
- From/To date pickers
- Clear filters button

### **Data Table**
Responsive table displaying:
- Email timestamp
- Recipient address
- Email subject
- Delivery status
- Retry attempts
- Action buttons

### **Log Details Modal**
Detailed view showing:
- Complete email metadata
- Error messages (if any)
- Variables used for personalization
- Attachment information
- Retry options

## 📱 Responsive Design

### **Tailwind CSS Features**
- Mobile-first responsive design
- Hover effects and transitions
- Alpine.js integration for interactions
- Modern grid layouts
- Accessible form controls

### **Bootstrap Features**
- Bootstrap 5 compatible
- Responsive breakpoints
- Modal dialogs
- Badge status indicators
- Card-based layouts
- Form validation styling

## 🔍 Filtering Examples

### **Basic Filtering**
```blade
{{-- Search for specific recipient --}}
<input wire:model.live="search" type="text" placeholder="Search emails...">

{{-- Filter by status --}}
<select wire:model.live="statusFilter">
    <option value="">All Statuses</option>
    <option value="sent">Sent</option>
    <option value="failed">Failed</option>
    <option value="pending">Pending</option>
</select>

{{-- Date range filters --}}
<input wire:model.live="dateFrom" type="date">
<input wire:model.live="dateTo" type="date">
```

### **Advanced Search**
The component supports searching across multiple fields:
- **Recipient Email**: Find emails to specific addresses
- **Subject**: Search email subjects
- **Error Messages**: Filter by error descriptions

### **Date Filtering**
- **From Date**: Emails sent on or after this date
- **To Date**: Emails sent on or before this date
- **Combined Range**: Use both for specific date ranges

## 📊 Export Functionality

### **CSV Export**
```php
// Export to CSV format
$exportFormat = 'csv';
// Creates: mass-mailer-logs-2024-01-15-14-30-25.csv
```

**CSV Structure:**
```
Date,Recipient Email,Subject,Status,Attempts,Error Message,Sent At
2024-01-15 14:30:25,john@example.com,Welcome to our service,Sent,1,,2024-01-15 14:30:25
```

### **JSON Export**
```php
// Export to JSON format
$exportFormat = 'json';
// Creates: mass-mailer-logs-2024-01-15-14-30-25.json
```

**JSON Structure:**
```json
[
    {
        "id": 123,
        "date": "2024-01-15 14:30:25",
        "recipient_email": "john@example.com",
        "subject": "Welcome to our service",
        "status": "sent",
        "attempts": 1,
        "error_message": null,
        "sent_at": "2024-01-15 14:30:25",
        "variables": {"name": "John", "email": "john@example.com"},
        "attachments": []
    }
]
```

## 🔄 Retry Functionality

### **Failed Email Retry**
When an email fails to send, the component provides a retry option:

```php
// Retry a specific failed email
retryFailedEmail($logId);

// This will:
// 1. Update log status from 'failed' to 'pending'
// 2. Clear the error message
// 3. Increment the attempt count
// 4. Queue the email for retry
```

### **Retry Logic**
```php
public function retryFailedEmail($logId)
{
    $log = MassMailerLog::find($logId);

    if ($log && $log->status === 'failed') {
        $log->update([
            'status' => 'pending',
            'error_message' => null,
            'attempts' => $log->attempts + 1,
        ]);

        // TODO: Implement actual retry mechanism
        // This would create a new job to resend the email
    }
}
```

## 🧹 Data Management

### **Clear Old Logs**
```php
// Remove logs older than 6 months
clearOldLogs();

// Confirmation dialog
// "Are you sure you want to clear old logs? This action cannot be undone."
```

### **Log Retention**
- **Default Retention**: 6 months
- **Configurable**: Modify in component method
- **User Scope**: Each user only clears their own logs
- **Confirmation Required**: Prevents accidental deletion

## 🔧 Customization

### **Framework Selection**
The component automatically detects your configured framework:

```php
// config/mass-mailer.php
'ui' => [
    'framework' => 'tailwind', // or 'bootstrap'
],
```

### **Pagination Settings**
```php
// Available page sizes
$perPage = 10; // 10, 25, 50, or 100
```

### **Query String Parameters**
The component maintains filter state in URLs:
```
/admin/email-logs?search=john&statusFilter=failed&dateFrom=2024-01-01&page=2
```

## 📋 Usage Examples

### **Basic Implementation**
```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/emails/logs', MassMailerLogs::class)->name('email.logs');
});
```

### **With Authorization**
```php
// Only allow admins to view logs
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/emails/logs', MassMailerLogs::class)->name('admin.email.logs');
});
```

### **With Custom Layout**
```blade
{{-- Use a custom layout --}}
@extends('layouts.admin')

@section('content')
    <livewire:mass-mailer-logs />
@endsection
```

### **With Navigation**
```blade
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">Admin</a>
        <div class="navbar-nav">
            <a class="nav-link" href="{{ route('mass-mailer-logs') }}">Email Logs</a>
        </div>
    </div>
</nav>

<div class="container-fluid mt-3">
    <livewire:mass-mailer-logs />
</div>
```

## 🚨 Security Features

### **User Data Isolation**
```php
// All queries are automatically scoped to the authenticated user
$query->where('user_id', auth()->id());
```

### **Authorization**
```php
// Check permissions before allowing actions
public function clearOldLogs()
{
    if (!auth()->user()->can('clear-email-logs')) {
        abort(403);
    }

    // Proceed with clearing logs...
}
```

### **Export Security**
- Users can only export their own logs
- No sensitive data exposure in exports
- Rate limiting on export requests

## 📈 Performance Considerations

### **Efficient Queries**
- Indexed database columns for fast filtering
- Paginated results for large datasets
- Query optimization for search operations

### **Caching**
```php
// Cache frequently accessed statistics
$stats = Cache::remember(
    "email_stats_" . auth()->id(),
    300, // 5 minutes
    function() {
        return $this->getStatistics();
    }
);
```

### **Database Optimization**
Ensure proper indexes exist:
```sql
-- These should be created by the migration
CREATE INDEX idx_mass_mailer_logs_user_id ON mass_mailer_logs(user_id);
CREATE INDEX idx_mass_mailer_logs_status ON mass_mailer_logs(status);
CREATE INDEX idx_mass_mailer_logs_created_at ON mass_mailer_logs(created_at);
```

## 🐛 Troubleshooting

### **Common Issues**

**No logs showing:**
- Check if emails have been sent
- Verify user has permission to view logs
- Check database connection

**Export not working:**
- Verify file permissions
- Check PHP memory limit for large exports
- Ensure proper headers are set

**Search not working:**
- Check if search index is built
- Verify database contains the expected data

**Pagination issues:**
- Ensure proper pagination configuration
- Check Livewire pagination theme settings

### **Debug Mode**
Enable debug logging:
```php
// config/mass-mailer.php
'logging' => [
    'enabled' => true,
    'level' => 'debug',
],
```

## 📚 Integration Tips

### **With Admin Panels**
```php
// Spatie Menu (example)
Menu::new()
    ->add(Item::fromRoute('mass-mailer-logs', 'Email Logs'))
    ->addTo(AdminSidebar::new());
```

### **With Laravel Telescope**
The component logs are automatically visible in Telescope if enabled.

### **With Notification Systems**
```php
// Send notifications for high failure rates
if ($failureRate > 10) {
    Notification::route('mail', 'admin@example.com')
        ->notify(new HighFailureRateNotification($failureRate));
}
```

The MassMailerLogs component provides a complete solution for monitoring and managing your email campaigns with professional-grade features and user experience.
