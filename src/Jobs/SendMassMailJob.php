<?php

namespace Mrclln\MassMailer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Mrclln\MassMailer\Mail\MassMailerMail;
use Mrclln\MassMailer\Models\MassMailerLog;

class SendMassMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes

    protected $recipients;
    protected $subject;
    protected $body;
    protected $globalAttachments;
    protected $sameAttachmentForAll;
    protected $senderCredentials;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $recipients,
        string $subject,
        string $body,
        ?array $globalAttachments = null,
        bool $sameAttachmentForAll = true,
        ?array $senderCredentials = null,
        ?int $userId = null
    ) {
        $this->recipients = $recipients;
        $this->subject = $subject;
        $this->body = $body;
        $this->globalAttachments = $globalAttachments;
        $this->sameAttachmentForAll = $sameAttachmentForAll;
        $this->senderCredentials = $senderCredentials;
        $this->userId = $userId;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set sender credentials if provided
        if ($this->senderCredentials) {
            $requiredKeys = ['host', 'port', 'username', 'password', 'encryption'];
            foreach ($requiredKeys as $key) {
                if (!isset($this->senderCredentials[$key])) {
                    Log::error("Missing required sender credential: {$key}");
                    throw new \Exception("Missing required sender credential: {$key}");
                }
            }

            // Clear any cached mail configuration first
            app()->forgetInstance('mail.manager');
            app()->forgetInstance('mailer');

            // Set the new SMTP configuration
            $currentMailConfig = config('mail.mailers.smtp');
            $newConfig = array_merge($currentMailConfig, $this->senderCredentials);
            config(['mail.mailers.smtp' => $newConfig]);
            config(['mail.default' => 'smtp']);

            // Also set the from address configuration
            config([
                'mail.from.address' => $this->senderCredentials['email'],
                'mail.from.name' => $this->senderCredentials['name']
            ]);
        }

        $batchSize = config('mass-mailer.batch_size', 50);
        $recipients = collect($this->recipients);

        // Process in batches
        $recipients->chunk($batchSize)->each(function ($batch) {
            $this->processBatch($batch);
        });

        // Clean up attachment files after sending
        $this->cleanupAttachments();

        // Log completion
        if (config('mass-mailer.logging.enabled', true)) {
            Log::info('Mass mail job completed', [
                'total_recipients' => count($this->recipients),
                'batches' => ceil(count($this->recipients) / $batchSize),
                'subject' => $this->subject,
            ]);
        }
    }

    /**
     * Process a batch of recipients.
     */
    protected function processBatch(Collection $batch): void
    {
        foreach ($batch as $recipient) {
            try {
                $this->sendToRecipient($recipient, $this->senderCredentials);

                // Rate limiting
                if (config('mass-mailer.rate_limiting.enabled', true)) {
                    sleep(1); // Simple rate limiting - 1 email per second
                }

            } catch (\Swift_TransportException $e) {
                Log::error('SMTP Transport Exception during batch processing', [
                    'recipient' => $recipient['email'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                $this->handleSendError($recipient, $e);
            } catch (\Exception $e) {
                $this->handleSendError($recipient, $e);
            }
        }
    }

    /**
     * Send email to a single recipient.
     */
    protected function sendToRecipient(array $recipient, ?array $senderCredentials = null): void
    {
        $email = $recipient['email'] ?? null;
        if (!$email) {
            Log::warning('Recipient missing email address', ['recipient' => $recipient]);
            return;
        }

        // Prepare personalized content
        $personalizedSubject = $this->personalizeContent($this->subject, $recipient);
        $personalizedBody = $this->personalizeContent($this->body, $recipient);

        // Prepare attachments
        $attachments = $this->prepareAttachments($recipient);

        // Prepare CC recipients
        $ccRecipients = $this->getCcRecipients($recipient);

        // Check if email view exists
        if (!View::exists('mass-mailer::emails.mass-mail')) {
            Log::error('Email view does not exist: mass-mailer::emails.mass-mail');
            throw new \Exception('Email view does not exist');
        }

        // Create log entry for this email
        $logEntry = MassMailerLog::logEmailPending(
            $email,
            $personalizedSubject,
            $personalizedBody,
            $recipient,
            $attachments,
            $this->userId,
            $this->job?->getJobId()
        );

        try {
            // Send the email using direct Mail::send for better attachment handling
            Mail::send([], [], function ($message) use ($email, $personalizedSubject, $personalizedBody, $attachments, $ccRecipients, $senderCredentials) {
                $message->to($email)
                    ->subject($personalizedSubject);

                // Add CC recipients if any
                if (!empty($ccRecipients)) {
                    foreach ($ccRecipients as $ccRecipient) {
                        $message->cc($ccRecipient['email']);
                    }
                }

                // Set from address if sender credentials provided
                if ($senderCredentials) {
                    $fromEmail = $senderCredentials['email'] ?? config('mail.from.address');
                    $fromName = $senderCredentials['name'] ?? config('mail.from.name');
                    $message->from($fromEmail, $fromName);
                } else {
                    // Use default from address
                    $fromEmail = config('mail.from.address');
                    $fromName = config('mail.from.name');
                    $message->from($fromEmail, $fromName);
                }

                // Use HTML template for body
                $htmlBody = view('mass-mailer::emails.mass-mail', [
                    'subject' => $personalizedSubject,
                    'body' => $personalizedBody
                ])->render();

                $message->html($htmlBody);

                // Attach files
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? 'application/octet-stream',
                        ]);
                    } else {
                        Log::warning('Attachment file not found', [
                            'path' => $attachment['path'] ?? 'null',
                            'exists' => isset($attachment['path']) ? file_exists($attachment['path']) : false
                        ]);
                    }
                }
            });

            // Update log entry as sent
            $logEntry->update([
                'status' => 'sent',
                'sent_at' => now()
            ]);

            // Log successful send
            if (config('mass-mailer.logging.enabled', true)) {
                Log::info('Email sent successfully', [
                    'recipient' => $email,
                    'subject' => $personalizedSubject,
                    'user_id' => $this->userId,
                    'log_id' => $logEntry->id
                ]);
            }

        } catch (\Exception $e) {
            // Update log entry as failed
            $logEntry->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'attempts' => $this->attempts()
            ]);

            // Log the error
            Log::error('Email sending failed', [
                'recipient' => $email,
                'subject' => $personalizedSubject,
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
                'log_id' => $logEntry->id,
                'attempts' => $this->attempts()
            ]);

            // Re-throw the exception to trigger job retry logic
            throw $e;
        }
    }

    /**
     * Personalize content by replacing variables.
     */
    public function personalizeContent(string $content, array $recipient): string
    {
        foreach ($recipient as $key => $value) {
            if ($key !== 'attachments' && !is_array($value)) {
                $content = str_replace("{{ {$key} }}", $value, $content);
                $content = str_replace("{{{$key}}}", $value, $content);
            }
        }
        return $content;
    }

    /**
     * Prepare attachments for the recipient.
     */
    protected function prepareAttachments(array $recipient): array
    {
        $attachments = [];

        // Add global attachments if applicable
        if ($this->sameAttachmentForAll && $this->globalAttachments) {
            $attachments = array_merge($attachments, $this->globalAttachments);
        }

        // Add recipient-specific attachments
        if (!$this->sameAttachmentForAll && isset($recipient['attachments'])) {
            $attachments = array_merge($attachments, $recipient['attachments']);
        }

        // Add recipient-specific attachments when sameAttachmentForAll is true and attachments are available
        if ($this->sameAttachmentForAll && isset($recipient['attachments']) && is_array($recipient['attachments'])) {
            $attachments = array_merge($attachments, $recipient['attachments']);
        }

        // Add auto-detected attachments (for both sameAttachmentForAll and per-recipient modes)
        if (isset($recipient['_auto_attachments']) && is_array($recipient['_auto_attachments'])) {
            $attachments = array_merge($attachments, $recipient['_auto_attachments']);
        }

        return $attachments;
    }

    /**
     * Get CC recipients for the recipient.
     */
    protected function getCcRecipients(array $recipient): array
    {
        $ccRecipients = [];

        // Add auto-detected CC from CSV
        if (isset($recipient['_auto_cc']) && is_array($recipient['_auto_cc'])) {
            $ccRecipients = array_merge($ccRecipients, $recipient['_auto_cc']);
        }

        return $ccRecipients;
    }

    /**
     * Clean up attachment files after sending.
     */
    protected function cleanupAttachments(): void
    {
        $storageDisk = config('mass-mailer.attachments.storage_disk', 'public');

        if ($this->sameAttachmentForAll && is_array($this->globalAttachments)) {
            foreach ($this->globalAttachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path']) && !($attachment['auto_detected'] ?? false)) {
                    unlink($attachment['path']);
                    Log::info('Deleted global attachment file: ' . $attachment['path']);
                } elseif (isset($attachment['auto_detected']) && $attachment['auto_detected']) {
                    Log::info('Skipped deletion of auto-detected global attachment: ' . ($attachment['path'] ?? 'unknown'));
                }
            }
        } else {
            foreach ($this->recipients as $recipient) {
                if (isset($recipient['attachments']) && is_array($recipient['attachments'])) {
                    foreach ($recipient['attachments'] as $attachment) {
                        if (isset($attachment['path']) && file_exists($attachment['path']) && !($attachment['auto_detected'] ?? false)) {
                            unlink($attachment['path']);
                            Log::info('Deleted per-recipient attachment file: ' . $attachment['path']);
                        } elseif (isset($attachment['auto_detected']) && $attachment['auto_detected']) {
                            Log::info('Skipped deletion of auto-detected attachment: ' . ($attachment['path'] ?? 'unknown'));
                        }
                    }
                }
            }
        }
    }

    /**
     * Handle send errors.
     */
    protected function handleSendError(array $recipient, \Exception $e): void
    {
        $email = $recipient['email'] ?? 'unknown';

        if ($e instanceof \Swift_TransportException) {
            Log::error('SMTP Transport Exception: Failed to send mass mail to recipient', [
                'recipient' => $email,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'user_id' => $this->userId,
            ]);
        } else {
            Log::error('Failed to send mass mail to recipient', [
                'recipient' => $email,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'user_id' => $this->userId,
            ]);
        }

        // If this is the last attempt, we might want to store failed emails
        if ($this->attempts() >= $this->tries) {
            $this->storeFailedEmail($recipient, $e);
        }
    }

    /**
     * Store failed email for later retry or analysis.
     */
    protected function storeFailedEmail(array $recipient, \Exception $e): void
    {
        // This could be stored in a database table if logging is enabled
        if (config('mass-mailer.logging.enabled', true)) {
            // Update or create log entry for permanently failed email
            MassMailerLog::logEmailFailed(
                $recipient['email'] ?? 'unknown',
                $this->subject,
                $e->getMessage(),
                $this->body,
                $recipient,
                $this->prepareAttachments($recipient),
                $this->userId,
                $this->job?->getJobId(),
                $this->attempts()
            );

            Log::warning('Email failed permanently', [
                'recipient' => $recipient['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'job_id' => $this->job?->getJobId(),
                'user_id' => $this->userId,
                'attempts' => $this->attempts(),
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Mass mail job failed completely', [
            'error' => $exception->getMessage(),
            'recipient_count' => count($this->recipients),
            'subject' => $this->subject,
        ]);
    }
}
