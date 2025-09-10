<?php

namespace App\Livewire\Admin\Tools;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Jobs\SendMassMailJob;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
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

    public $hasEmailCredentials = false;


    public function mount()
    {
        $this->hasEmailCredentials = Auth::user()->emailCredential ? true : false;
    }

    #[On('sendMassMail')]
    public function sendMassMail($payload, $subjectTemplate, $bodyTemplate, $sameAttachmentForAll = true)
    {
        $subject = is_array($subjectTemplate) ? implode(' ', $subjectTemplate) : (string) $subjectTemplate;
        $body = is_array($bodyTemplate) ? implode(' ', $bodyTemplate) : (string) $bodyTemplate;

        $storedGlobalAttachments = [];

        // ✅ Handle and store global attachments
        if ($sameAttachmentForAll && is_array($this->globalAttachments)) {
            foreach ($this->globalAttachments as $file) {
                $path = $file->store('mass_mail/global');
                $storedGlobalAttachments[] = [
                    'path' => storage_path('app/' . $path),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                ];
            }
        }

        // ✅ Handle and store per-recipient attachments
        $updatedPayload = [];
        foreach ($payload as $i => $recipient) {
            $recipientAttachments = [];

            if (!$sameAttachmentForAll && isset($this->perRecipientAttachments[$i])) {
                foreach ($this->perRecipientAttachments[$i] as $file) {
                    $path = $file->store("mass_mail/recipients/{$i}");
                    $recipientAttachments[] = [
                        'path' => storage_path('app/' . $path),
                        'name' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                    ];
                }
            }

            // Add attachments to the recipient data before adding it to the updated payload.
            $recipient['attachments'] = $recipientAttachments;
            $updatedPayload[] = $recipient;
        }

        // ✅ Dispatch the job with stored paths
        SendMassMailJob::dispatch(
            $updatedPayload, // Use the updated payload here
            $subject,
            $body,
            $sameAttachmentForAll ? $storedGlobalAttachments : null,
            $sameAttachmentForAll
        );

        LivewireAlert::success()
            ->title('Yay!!')
            ->text('Emails have been sent!')
            ->withConfirmButton('Okay')
            ->confirmButtonColor('#31651e')
            ->show();
    }
}
