<?php

namespace Mrclln\MassMailer\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Mrclln\MassMailer\Models\MassMailerLog;
use Mrclln\MassMailer\Models\MassMailerSender;

trait MassMailerUserTrait
{
    /**
     * Get all mass mailer logs for this user
     */
    public function massMailerLogs()
    {
        return $this->hasMany(MassMailerLog::class, 'user_id');
    }

    /**
     * Get all mass mailer senders for this user
     */
    public function massMailerSenders()
    {
        return $this->hasMany(MassMailerSender::class, 'user_id');
    }

    /**
     * Get successful email logs
     */
    public function successfulMassMailerLogs()
    {
        return $this->massMailerLogs()->where('status', 'sent');
    }

    /**
     * Get failed email logs
     */
    public function failedMassMailerLogs()
    {
        return $this->massMailerLogs()->where('status', 'failed');
    }

    /**
     * Get pending email logs
     */
    public function pendingMassMailerLogs()
    {
        return $this->massMailerLogs()->where('status', 'pending');
    }

    /**
     * Get email logs within a date range
     */
    public function massMailerLogsInDateRange($startDate, $endDate)
    {
        return $this->massMailerLogs()->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get email logs by status within a date range
     */
    public function massMailerLogsByStatusInDateRange($status, $startDate, $endDate)
    {
        return $this->massMailerLogs()
            ->where('status', $status)
            ->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get email statistics for this user
     */
    public function getMassMailerStats()
    {
        return MassMailerLog::getStatsForUser($this->id);
    }

    /**
     * Get email success rate for this user
     */
    public function getMassMailerSuccessRate()
    {
        return MassMailerLog::getSuccessRateForUser($this->id);
    }

    /**
     * Get total emails sent by this user
     */
    public function getTotalMassMailerEmails()
    {
        return $this->massMailerLogs()->count();
    }

    /**
     * Get successful emails count for this user
     */
    public function getSuccessfulMassMailerEmails()
    {
        return $this->successfulMassMailerLogs()->count();
    }

    /**
     * Get failed emails count for this user
     */
    public function getFailedMassMailerEmails()
    {
        return $this->failedMassMailerLogs()->count();
    }

    /**
     * Get pending emails count for this user
     */
    public function getPendingMassMailerEmails()
    {
        return $this->pendingMassMailerLogs()->count();
    }

    /**
     * Get emails sent today by this user
     */
    public function getMassMailerEmailsSentToday()
    {
        return $this->successfulMassMailerLogs()
            ->whereDate('sent_at', today())
            ->count();
    }

    /**
     * Get emails sent this week by this user
     */
    public function getMassMailerEmailsSentThisWeek()
    {
        return $this->successfulMassMailerLogs()
            ->whereBetween('sent_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
    }

    /**
     * Get emails sent this month by this user
     */
    public function getMassMailerEmailsSentThisMonth()
    {
        return $this->successfulMassMailerLogs()
            ->whereMonth('sent_at', now()->month)
            ->whereYear('sent_at', now()->year)
            ->count();
    }

    /**
     * Get recent email activity for this user
     */
    public function getRecentMassMailerActivity($limit = 10)
    {
        return $this->massMailerLogs()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed emails that need attention
     */
    public function getMassMailerFailedEmailsNeedingAttention()
    {
        return $this->failedMassMailerLogs()
            ->where('attempts', '>=', 2) // Emails that failed multiple times
            ->latest()
            ->get();
    }

    /**
     * Get most common failure reasons for this user
     */
    public function getMassMailerCommonFailureReasons($limit = 5)
    {
        return $this->failedMassMailerLogs()
            ->select('error_message', DB::raw('count(*) as failure_count'))
            ->groupBy('error_message')
            ->orderBy('failure_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get email statistics by time period
     */
    public function getMassMailerStatsByPeriod($period = 'week')
    {
        $query = $this->massMailerLogs();

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $total = $query->count();
        $sent = (clone $query)->where('status', 'sent')->count();
        $failed = (clone $query)->where('status', 'failed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();

        return [
            'period' => $period,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get email performance trends
     */
    public function getMassMailerPerformanceTrends($days = 30)
    {
        return $this->massMailerLogs()
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_emails,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as successful_emails,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_emails,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as success_rate
            ')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Check if user has sent any emails
     */
    public function hasSentMassMailerEmails()
    {
        return $this->successfulMassMailerLogs()->exists();
    }

    /**
     * Check if user has any failed emails
     */
    public function hasFailedMassMailerEmails()
    {
        return $this->failedMassMailerLogs()->exists();
    }

    /**
     * Get unique recipients this user has emailed
     */
    public function getMassMailerUniqueRecipients()
    {
        return $this->successfulMassMailerLogs()
            ->distinct('recipient_email')
            ->pluck('recipient_email')
            ->count();
    }

    /**
     * Get most emailed recipients
     */
    public function getMassMailerTopRecipients($limit = 10)
    {
        return $this->successfulMassMailerLogs()
            ->selectRaw('recipient_email, COUNT(*) as email_count')
            ->groupBy('recipient_email')
            ->orderBy('email_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get email subjects this user has used
     */
    public function getMassMailerUsedSubjects()
    {
        return $this->massMailerLogs()
            ->distinct('subject')
            ->pluck('subject')
            ->sort()
            ->values();
    }

    /**
     * Clear mass mailer logs for this user (use with caution)
     */
    public function clearMassMailerLogs($beforeDate = null)
    {
        $query = $this->massMailerLogs();

        if ($beforeDate) {
            $query->where('created_at', '<', $beforeDate);
        }

        return $query->delete();
    }

    /**
     * Export mass mailer logs for this user
     */
    public function exportMassMailerLogs($format = 'array', $startDate = null, $endDate = null)
    {
        $query = $this->massMailerLogs()->latest();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $logs = $query->get();

        switch ($format) {
            case 'array':
                return $logs->toArray();
            case 'csv':
                // Return CSV formatted data
                $csvData = [];
                $csvData[] = ['Date', 'Recipient', 'Subject', 'Status', 'Error Message', 'Attempts'];

                foreach ($logs as $log) {
                    $csvData[] = [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->recipient_email,
                        $log->subject,
                        $log->status,
                        $log->error_message ?? '',
                        $log->attempts,
                    ];
                }

                return $csvData;
            default:
                return $logs;
        }
    }

    /**
     * Get mass mailer activity summary
     */
    public function getMassMailerActivitySummary()
    {
        $stats = $this->getMassMailerStats();
        $todayStats = $this->getMassMailerStatsByPeriod('today');
        $weekStats = $this->getMassMailerStatsByPeriod('week');
        $monthStats = $this->getMassMailerStatsByPeriod('month');

        return [
            'overall' => $stats,
            'today' => $todayStats,
            'this_week' => $weekStats,
            'this_month' => $monthStats,
            'unique_recipients' => $this->getMassMailerUniqueRecipients(),
            'has_sent_emails' => $this->hasSentMassMailerEmails(),
            'has_failed_emails' => $this->hasFailedMassMailerEmails(),
            'top_recipients' => $this->getMassMailerTopRecipients(5),
            'recent_activity' => $this->getRecentMassMailerActivity(5),
        ];
    }
}
