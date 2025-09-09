<?php

namespace Mrclln\MassMailer\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Mrclln\MassMailer\Jobs\SendMassMailJob;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class MassMailer extends Component
{
    use WithFileUploads;

    public $recipients = [];
    public $globalAttachments = [];
    public $perRecipientAttachments = [];
    public $subject;
    public $body;
    public $sameAttachmentForAll = true;

    public $hasEmailCredentials = true; // Default to true for package
    public $defaultVariables = [];

    public function mount()
    {
        // Check if mass mailer is enabled
        if (!config('mass-mailer.enabled', true)) {
            $this->hasEmailCredentials = false;
            return;
        }

        // Initialize default variables from config
        $this->defaultVariables = config('mass-mailer.ui.variables', ['email', 'first_name', 'last_name']);
        $this->recipients = [
            array_fill_keys($this->defaultVariables, '')
        ];
    }

    protected function rules()
    {
        $maxSize = config('mass-mailer.attachments.max_size', 10240) / 1024;

        return [
            'globalAttachments.*' => 'file|max:' . $maxSize,
            'perRecipientAttachments.*.*' => 'file|max:' . $maxSize,
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ];
    }

    #[On('sendMassMail')]
    public function sendMassMail($payload, $subjectTemplate, $bodyTemplate, $sameAttachmentForAll = true)
    {
        $this->validate();

        $subject = is_array($subjectTemplate) ? implode(' ', $subjectTemplate) : (string) $subjectTemplate;
        $body = is_array($bodyTemplate) ? implode(' ', $bodyTemplate) : (string) $bodyTemplate;

        $storedGlobalAttachments = [];

        // Handle and store global attachments
        if ($sameAttachmentForAll && is_array($this->globalAttachments)) {
            foreach ($this->globalAttachments as $file) {
                $path = $file->store(config('mass-mailer.attachments.storage_disk') . '/mass_mail/global');
                $storedGlobalAttachments[] = [
                    'path' => storage_path('app/' . $path),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                ];
            }
        }

        // Handle and store per-recipient attachments
        $updatedPayload = [];
        foreach ($payload as $i => $recipient) {
            $recipientAttachments = [];

            if (!$sameAttachmentForAll && isset($this->perRecipientAttachments[$i])) {
                foreach ($this->perRecipientAttachments[$i] as $file) {
                    $path = $file->store(config('mass-mailer.attachments.storage_disk') . "/mass_mail/recipients/{$i}");
                    $recipientAttachments[] = [
                        'path' => storage_path('app/' . $path),
                        'name' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                    ];
                }
            }

            // Add attachments to the recipient data
            $recipient['attachments'] = $recipientAttachments;
            $updatedPayload[] = $recipient;
        }

        try {
            // Dispatch the job with configured queue
            SendMassMailJob::dispatch(
                $updatedPayload,
                $subject,
                $body,
                $sameAttachmentForAll ? $storedGlobalAttachments : null,
                $sameAttachmentForAll
            )->onQueue(config('mass-mailer.queue.name', 'mass-mailer'));

            // Log the action if logging is enabled
            if (config('mass-mailer.logging.enabled', true)) {
                Log::info('Mass mail dispatched', [
                    'recipient_count' => count($updatedPayload),
                    'subject' => $subject,
                    'has_attachments' => !empty($storedGlobalAttachments) || collect($updatedPayload)->pluck('attachments')->flatten()->isNotEmpty(),
                ]);
            }


            LivewireAlert::success()
                ->title('Success!')
                ->text('Emails have been queued for sending!')
                ->withConfirmButton('Okay')
                ->confirmButtonColor('#31651e')
                ->show();

        } catch (\Exception $e) {
            Log::error('Failed to dispatch mass mail job', [
                'error' => $e->getMessage(),
                'recipient_count' => count($updatedPayload),
            ]);

            LivewireAlert::error()
                ->title('Error!')
                ->text('Failed to send emails. Please try again.')
                ->show();
        }
    }

    public function render()
    {
        $framework = config('mass-mailer.ui.framework', 'bootstrap');
        $viewName = 'mass-mailer::' . $framework . '.mass-mailer';

        return view($viewName);
    }
}
