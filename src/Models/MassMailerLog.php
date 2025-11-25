<?php

namespace Mrclln\MassMailer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class MassMailerLog extends Model
{
    use HasFactory;

    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('mass-mailer.logging.table', 'mass_mailer_logs');
    }

    protected $fillable = [
        'user_id',
        'job_id',
        'recipient_email',
        'subject',
        'body',
        'variables',
        'attachments',
        'status',
        'error_message',
        'attempts',
        'sent_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'attachments' => 'array',
        'sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * Get the user that owns the log entry
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get logs by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get logs by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get logs by date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get success rate percentage for a user
     */
    public static function getSuccessRateForUser($userId)
    {
        $total = self::where('user_id', $userId)->count();

        if ($total === 0) {
            return 0;
        }

        $successful = self::where('user_id', $userId)
            ->where('status', 'sent')
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get email statistics for a user
     */
    public static function getStatsForUser($userId)
    {
        return [
            'total' => self::where('user_id', $userId)->count(),
            'sent' => self::where('user_id', $userId)->where('status', 'sent')->count(),
            'failed' => self::where('user_id', $userId)->where('status', 'failed')->count(),
            'pending' => self::where('user_id', $userId)->where('status', 'pending')->count(),
            'success_rate' => self::getSuccessRateForUser($userId),
        ];
    }

    /**
     * Create a log entry for email sending
     */
    public static function logEmailSent(
        $recipientEmail,
        $subject,
        $body = null,
        $variables = null,
        $attachments = null,
        $userId = null,
        $jobId = null
    ) {
        return self::create([
            'user_id' => $userId,
            'job_id' => $jobId,
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'body' => $body,
            'variables' => $variables,
            'attachments' => $attachments,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Create a log entry for email failure
     */
    public static function logEmailFailed(
        $recipientEmail,
        $subject,
        $errorMessage,
        $body = null,
        $variables = null,
        $attachments = null,
        $userId = null,
        $jobId = null,
        $attempts = 1
    ) {
        return self::create([
            'user_id' => $userId,
            'job_id' => $jobId,
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'body' => $body,
            'variables' => $variables,
            'attachments' => $attachments,
            'status' => 'failed',
            'error_message' => $errorMessage,
            'attempts' => $attempts,
        ]);
    }

    /**
     * Create a log entry for pending email
     */
    public static function logEmailPending(
        $recipientEmail,
        $subject,
        $body = null,
        $variables = null,
        $attachments = null,
        $userId = null,
        $jobId = null
    ) {
        return self::create([
            'user_id' => $userId,
            'job_id' => $jobId,
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'body' => $body,
            'variables' => $variables,
            'attachments' => $attachments,
            'status' => 'pending',
        ]);
    }
}
