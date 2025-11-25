# Mass Mailer User Trait - Integration Guide

The `MassMailerUserTrait` provides a comprehensive set of methods for integrating Mass Mailer logging functionality into your User model. This trait makes it easy to track email campaigns, get statistics, and monitor user activity.

## 🚀 Quick Start

### 1. Add Trait to Your User Model

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Mrclln\MassMailer\Traits\MassMailerUserTrait;

class User extends Authenticatable
{
    use MassMailerUserTrait;

    // Your existing User model code...
}
```

### 2. Run Migration

```bash
php artisan migrate
```

That's it! Your User model now has full Mass Mailer functionality.

## 📊 Available Methods

### Basic Relationships

```php
// Get all email logs for a user
$user->massMailerLogs();

// Get only successful emails
$user->successfulMassMailerLogs();

// Get only failed emails
$user->failedMassMailerLogs();

// Get only pending emails
$user->pendingMassMailerLogs();

// Get user's email senders
$user->massMailerSenders();
```

### Statistics & Analytics

```php
// Get comprehensive statistics
$stats = $user->getMassMailerStats();
/*
Returns:
[
    'total' => 150,
    'sent' => 145,
    'failed' => 3,
    'pending' => 2,
    'success_rate' => 96.67
]
*/

// Get success rate percentage
$successRate = $user->getMassMailerSuccessRate(); // 96.67

// Get total emails sent
$totalEmails = $user->getTotalMassMailerEmails();

// Get successful emails count
$successfulEmails = $user->getSuccessfulMassMailerEmails();

// Get failed emails count
$failedEmails = $user->getFailedMassMailerEmails();
```

### Time-based Analytics

```php
// Emails sent today
$todayEmails = $user->getMassMailerEmailsSentToday();

// Emails sent this week
$weekEmails = $user->getMassMailerEmailsSentThisWeek();

// Emails sent this month
$monthEmails = $user->getMassMailerEmailsSentThisMonth();

// Get statistics by period (today, week, month, year)
$todayStats = $user->getMassMailerStatsByPeriod('today');
$weekStats = $user->getMassMailerStatsByPeriod('week');
$monthStats = $user->getMassMailerStatsByPeriod('month');
```

### Activity & Monitoring

```php
// Get recent email activity (last 10 emails)
$recentActivity = $user->getRecentMassMailerActivity();

// Get failed emails needing attention (failed multiple times)
$failedEmails = $user->getMassMailerFailedEmailsNeedingAttention();

// Get most common failure reasons
$failureReasons = $user->getMassMailerCommonFailureReasons();

// Check if user has sent any emails
$hasSentEmails = $user->hasSentMassMailerEmails();

// Check if user has any failed emails
$hasFailedEmails = $user->hasFailedMassMailerEmails();
```

### Recipient Analysis

```php
// Get number of unique recipients
$uniqueRecipients = $user->getMassMailerUniqueRecipients();

// Get most emailed recipients (top 10)
$topRecipients = $user->getMassMailerTopRecipients(10);

// Get all subjects used by this user
$usedSubjects = $user->getMassMailerUsedSubjects();
```

### Performance & Trends

```php
// Get performance trends for last 30 days
$trends = $user->getMassMailerPerformanceTrends(30);
/*
Returns collection with:
- date
- total_emails
- successful_emails
- failed_emails
- success_rate
*/

// Get activity summary
$summary = $user->getMassMailerActivitySummary();
/*
Returns comprehensive summary including:
- overall statistics
- today/week/month breakdowns
- unique recipients count
- top recipients
- recent activity
*/
```

### Data Management

```php
// Export logs as array
$logsArray = $user->exportMassMailerLogs('array');

// Export logs as CSV data
$csvData = $user->exportMassMailerLogs('csv');

// Clear old logs (before specific date)
$deleted = $user->clearMassMailerLogs(now()->subMonths(6));

// Clear all logs for user
$deleted = $user->clearMassMailerLogs();
```

## 🎯 Real-world Usage Examples

### Dashboard Widget

```php
// In your controller or dashboard
public function dashboard()
{
    $user = auth()->user();

    $dashboardData = [
        'total_emails' => $user->getTotalMassMailerEmails(),
        'success_rate' => $user->getMassMailerSuccessRate(),
        'emails_today' => $user->getMassMailerEmailsSentToday(),
        'recent_activity' => $user->getRecentMassMailerActivity(5),
        'failed_emails' => $user->getMassMailerFailedEmailsNeedingAttention(),
    ];

    return view('dashboard', compact('dashboardData'));
}
```

### Blade Template Example

```blade
{{-- dashboard.blade.php --}}
<div class="email-stats">
    <div class="stat-card">
        <h3>{{ $dashboardData['total_emails'] }}</h3>
        <p>Total Emails Sent</p>
    </div>

    <div class="stat-card">
        <h3>{{ $dashboardData['success_rate'] }}%</h3>
        <p>Success Rate</p>
    </div>

    <div class="stat-card">
        <h3>{{ $dashboardData['emails_today'] }}</h3>
        <p>Emails Today</p>
    </div>
</div>

@if($dashboardData['failed_emails']->count() > 0)
<div class="alert alert-warning">
    <h4>Failed Emails Needing Attention</h4>
    @foreach($dashboardData['failed_emails'] as $failed)
        <p>{{ $failed->recipient_email }} - {{ $failed->error_message }}</p>
    @endforeach
</div>
@endif
```

### API Endpoint Example

```php
// In your API controller
public function userEmailStats(Request $request)
{
    $user = $request->user();

    return response()->json([
        'success' => true,
        'data' => [
            'statistics' => $user->getMassMailerStats(),
            'trends' => $user->getMassMailerPerformanceTrends(30),
            'top_recipients' => $user->getMassMailerTopRecipients(5),
            'summary' => $user->getMassMailerActivitySummary(),
        ]
    ]);
}
```

### Email Campaign Report

```php
public function generateCampaignReport(User $user, $startDate, $endDate)
{
    $report = [
        'user' => $user->name,
        'period' => $startDate . ' to ' . $endDate,
        'stats_by_period' => [
            'today' => $user->getMassMailerStatsByPeriod('today'),
            'week' => $user->getMassMailerStatsByPeriod('week'),
            'month' => $user->getMassMailerStatsByPeriod('month'),
        ],
        'trends' => $user->getMassMailerPerformanceTrends(30),
        'top_recipients' => $user->getMassMailerTopRecipients(10),
        'failure_analysis' => $user->getMassMailerCommonFailureReasons(5),
        'export_data' => $user->exportMassMailerLogs('array', $startDate, $endDate),
    ];

    return $report;
}
```

### Admin Analytics

```php
// For admin dashboard showing all users
public function adminAnalytics()
{
    $users = User::withCount([
        'massMailerLogs as total_emails',
        'massMailerLogs as sent_emails' => function($query) {
            $query->where('status', 'sent');
        },
        'massMailerLogs as failed_emails' => function($query) {
            $query->where('status', 'failed');
        }
    ])->having('total_emails', '>', 0)->get();

    return view('admin.analytics', compact('users'));
}
```

## 🔍 Advanced Query Examples

### Date Range Filtering

```php
// Get emails sent in the last week
$recentEmails = $user->massMailerLogsInDateRange(
    now()->subWeek(),
    now()
);

// Get failed emails this month
$monthlyFailures = $user->massMailerLogsByStatusInDateRange(
    'failed',
    now()->startOfMonth(),
    now()->endOfMonth()
);
```

### Custom Collections

```php
// Get all recipients for successful emails this month
$recipients = $user->successfulMassMailerLogs()
    ->whereMonth('sent_at', now()->month)
    ->whereYear('sent_at', now()->year)
    ->pluck('recipient_email')
    ->unique();

// Get subjects with their success rates
$subjectPerformance = $user->massMailerLogs()
    ->selectRaw('subject,
        COUNT(*) as total_sent,
        SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as successful,
        AVG(CASE WHEN status = "sent" THEN 1 ELSE 0 END) * 100 as success_rate')
    ->groupBy('subject')
    ->orderBy('success_rate', 'desc')
    ->get();
```

## 📈 Performance Considerations

### Eager Loading

```php
// Always use eager loading for better performance
$users = User::with('massMailerLogs', 'massMailerSenders')->get();

// For specific relationships
$user = User::with([
    'successfulMassMailerLogs',
    'failedMassMailerLogs'
])->find($id);
```

### Caching

```php
// Cache expensive queries
$stats = Cache::remember(
    "user_{$user->id}_massmailer_stats",
    3600, // 1 hour
    function() use ($user) {
        return $user->getMassMailerStats();
    }
);
```

### Pagination

```php
// Paginate large result sets
$recentLogs = $user->massMailerLogs()->latest()->paginate(20);

// Paginate with filters
$failedLogs = $user->failedMassMailerLogs()
    ->latest()
    ->paginate(20);
```

## 🔧 Configuration

### Custom Table Names

If you're using custom table names, make sure your configuration matches:

```php
// In config/mass-mailer.php
'logging' => [
    'table' => 'your_custom_logs_table', // Default: 'mass_mailer_logs'
    'enabled' => true,
],
```

### Database Indexes

Ensure your database has proper indexes for optimal performance:

```sql
-- These are automatically created by the migration
CREATE INDEX idx_mass_mailer_logs_user_id ON mass_mailer_logs(user_id);
CREATE INDEX idx_mass_mailer_logs_status ON mass_mailer_logs(status);
CREATE INDEX idx_mass_mailer_logs_created_at ON mass_mailer_logs(created_at);
```

## 🚨 Important Notes

### Security Considerations

- Users can only access their own email logs
- All queries are automatically scoped to the authenticated user
- No risk of data leakage between users

### Data Cleanup

```php
// Regularly clean up old logs to maintain performance
$deleted = $user->clearMassMailerLogs(now()->subYears(2));
```

### Error Handling

```php
try {
    $stats = $user->getMassMailerStats();
} catch (\Exception $e) {
    // Handle cases where migration hasn't been run
    // or table doesn't exist yet
    Log::error('Mass Mailer stats error: ' . $e->getMessage());
}
```

## 📚 Integration Checklist

- [ ] Add `MassMailerUserTrait` to User model
- [ ] Run migration: `php artisan migrate`
- [ ] Test basic functionality: `$user->massMailerLogs()->count()`
- [ ] Implement in dashboard/controller
- [ ] Add caching for frequently accessed data
- [ ] Set up regular data cleanup
- [ ] Add proper error handling
- [ ] Test with real email campaigns

## 🎯 Benefits

✅ **Easy Integration** - Just add trait to User model
✅ **Comprehensive Analytics** - Detailed statistics and trends
✅ **Performance Optimized** - Proper indexing and eager loading
✅ **Secure** - User data isolation built-in
✅ **Flexible** - Multiple export formats and filters
✅ **Extensible** - Easy to add custom methods

The `MassMailerUserTrait` provides everything needed to integrate Mass Mailer logging into your application with minimal effort while maintaining security and performance.
