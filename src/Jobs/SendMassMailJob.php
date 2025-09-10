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
use Mrclln\MassMailer\Mail\MassMailerMail;

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

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $recipients,
        string $subject,
        string $body,
        ?array $globalAttachments = null,
        bool $sameAttachmentForAll = true
    ) {
        $this->recipients = $recipients;
        $this->subject = $subject;
        $this->body = $body;
        $this->globalAttachments = $globalAttachments;
        $this->sameAttachmentForAll = $sameAttachmentForAll;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Debug: Log job execution
        Log::info('SendMassMailJob started', [
            'recipient_count' => count($this->recipients),
            'subject' => $this->subject,
            'same_attachment' => $this->sameAttachmentForAll,
            'global_attachments_count' => $this->globalAttachments ? count($this->globalAttachments) : 0,
            'first_recipient' => !empty($this->recipients) ? $this->recipients[0] : null
        ]);

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
                $this->sendToRecipient($recipient);

                // Rate limiting
                if (config('mass-mailer.rate_limiting.enabled', true)) {
                    sleep(1); // Simple rate limiting - 1 email per second
                }

            } catch (\Exception $e) {
                $this->handleSendError($recipient, $e);
            }
        }
    }

    /**
     * Send email to a single recipient.
     */
    protected function sendToRecipient(array $recipient): void
    {
        $email = $recipient['email'] ?? null;
        if (!$email) {
            Log::warning('Recipient missing email address', ['recipient' => $recipient]);
            return;
        }

        // Debug: Log recipient processing
        Log::info('Processing recipient', [
            'email' => $email,
            'recipient_data' => $recipient,
            'has_attachments' => isset($recipient['attachments']) && !empty($recipient['attachments'])
        ]);

        // Prepare personalized content
        $personalizedSubject = $this->personalizeContent($this->subject, $recipient);
        $personalizedBody = $this->personalizeContent($this->body, $recipient);

        // Prepare attachments
        $attachments = $this->prepareAttachments($recipient);

        Log::info('Sending email', [
            'to' => $email,
            'subject' => $personalizedSubject,
            'attachments_count' => count($attachments),
            'attachment_paths' => array_column($attachments, 'path')
        ]);

        // Send the email using direct Mail::send for better attachment handling
        Mail::send([], [], function ($message) use ($email, $personalizedSubject, $personalizedBody, $attachments) {
            $message->to($email)
                ->subject($personalizedSubject);

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

        // Log successful send
        if (config('mass-mailer.logging.enabled', true)) {
            Log::info('Email sent successfully', [
                'to' => $email,
                'subject' => $personalizedSubject,
                'has_attachments' => !empty($attachments),
            ]);
        }
    }

    /**
     * Personalize content by replacing variables.
     */
    protected function personalizeContent(string $content, array $recipient): string
    {
        foreach ($recipient as $key => $value) {
            if ($key !== 'attachments') {
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

        return $attachments;
    }

    /**
     * Clean up attachment files after sending.
     */
    protected function cleanupAttachments(): void
    {
        $storageDisk = config('mass-mailer.attachments.storage_disk', 'public');

        if ($this->sameAttachmentForAll && is_array($this->globalAttachments)) {
            foreach ($this->globalAttachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    unlink($attachment['path']);
                    Log::info('Deleted global attachment file: ' . $attachment['path']);
                }
            }
        } else {
            foreach ($this->recipients as $recipient) {
                if (isset($recipient['attachments']) && is_array($recipient['attachments'])) {
                    foreach ($recipient['attachments'] as $attachment) {
                        if (isset($attachment['path']) && file_exists($attachment['path'])) {
                            unlink($attachment['path']);
                            Log::info('Deleted per-recipient attachment file: ' . $attachment['path']);
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

        Log::error('Failed to send mass mail to recipient', [
            'recipient' => $email,
            'error' => $e->getMessage(),
            'attempt' => $this->attempts(),
        ]);

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
            // For now, just log it. In a real implementation, you might create a failed_emails table
            Log::warning('Email failed permanently', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId(),
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
