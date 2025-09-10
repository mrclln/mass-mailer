<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;

class SendMassMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;
    public string $subjectTemplate;
    public string $bodyTemplate;
    public ?array $globalAttachments;
    public bool $sameAttachmentForAll;

    /**
     * Create a new job instance.
     *
     * @param array $payload The array of recipients, with their data and attachments.
     * @param string $subjectTemplate The email subject template.
     * @param string $bodyTemplate The email body template.
     * @param ?array $globalAttachments An array of global attachment paths.
     * @param bool $sameAttachmentForAll A flag to indicate if attachments are global.
     */
    public function __construct(array $payload, string $subjectTemplate, string $bodyTemplate, ?array $globalAttachments = null, bool $sameAttachmentForAll = true)
    {
        $this->payload = $payload;
        $this->subjectTemplate = $subjectTemplate;
        $this->bodyTemplate = $bodyTemplate;
        $this->globalAttachments = $globalAttachments;
        $this->sameAttachmentForAll = $sameAttachmentForAll;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->payload as $recipient) {
            $data = $recipient;

            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                Log::warning('Invalid email skipped in mass mailer', ['data' => $data]);
                continue;
            }

            $attachments = $this->sameAttachmentForAll
                ? $this->globalAttachments
                : ($recipient['attachments'] ?? []);

            $subject = $this->parseTemplate($this->subjectTemplate, $data);
            $body = $this->parseTemplate($this->bodyTemplate, $data);

            Mail::send([], [], function ($message) use ($data, $subject, $body, $attachments) {
                $message->to($data['email'])
                    ->subject($subject)
                    ->html($body);

                foreach ($attachments as $attachment) {
                    $correctPath = str_replace('mass_mail', 'private/mass_mail', $attachment['path']);

                    if (
                        isset($attachment['path']) &&
                        file_exists($correctPath)
                    ) {
                        $message->attach($correctPath, [
                            'as' => $attachment['name'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? 'application/octet-stream',
                        ]);
                    }
                }
            });
        }

        // ---
        // FIX: Delete the files after sending the emails.
        // ---
        if ($this->sameAttachmentForAll) {
            if (is_array($this->globalAttachments)) {
                foreach ($this->globalAttachments as $attachment) {
                    $correctPath = str_replace('mass_mail', 'private/mass_mail', $attachment['path']);
                    if (file_exists($correctPath)) {
                        unlink($correctPath);
                        Log::info('Deleted global attachment file: ' . $correctPath);
                    }
                }
            }
        } else {
            foreach ($this->payload as $recipient) {
                if (isset($recipient['attachments']) && is_array($recipient['attachments'])) {
                    foreach ($recipient['attachments'] as $attachment) {
                        $correctPath = str_replace('mass_mail', 'private/mass_mail', $attachment['path']);
                        if (file_exists($correctPath)) {
                            unlink($correctPath);
                            Log::info('Deleted per-recipient attachment file: ' . $correctPath);
                        }
                    }
                }
            }
        }
    }

    /**
     * Parses a template string with given data.
     *
     * @param string $template The template string.
     * @param array $data The data to use for replacements.
     * @return string The parsed template.
     */
    protected function parseTemplate(string $template, array $data): string
    {
        return preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($matches) use ($data) {
            return $data[$matches[1]] ?? '';
        }, $template);
    }
}
