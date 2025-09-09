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
        $batchSize = config('mass-mailer.batch_size', 50);
        $recipients = collect($this->recipients);

        // Process in batches
        $recipients->chunk($batchSize)->each(function ($batch) {
            $this->processBatch($batch);
        });

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

        // Prepare personalized content
        $personalizedSubject = $this->personalizeContent($this->subject, $recipient);
        $personalizedBody = $this->personalizeContent($this->body, $recipient);

        // Prepare attachments
        $attachments = $this->prepareAttachments($recipient);

        // Send the email
        Mail::to($email)->send(new MassMailerMail(
            $personalizedSubject,
            $personalizedBody,
            $attachments
        ));

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
