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

  public array $recipients = [];
  public array $globalAttachments = [];
  public array $perRecipientAttachments = [];
  public $subject;
  public $body;
  public $sameAttachmentForAll = true;

  public $hasEmailCredentials = true; // Default to true for package
  public array $defaultVariables = [];
  public $sending = false;

  // Alpine.js state converted to Livewire
  public $newVariable = '';
  public array $variables = [];
  public $draggedVariable = '';
  public $csvFile;
  public $previewContent = '';
  public $showPreview = false;
  public $previewEmail = '';
  public $selectedRecipientIndex = null;
  public $selectedSender = null;
  public array $senders = [];

  public function mount() :void
  {
    // Check if mass mailer is enabled
    if (!config('mass-mailer.enabled', true)) {
      $this->hasEmailCredentials = false;
      return;
    }

    // Initialize default variables from config
    $this->defaultVariables = config('mass-mailer.ui.variables', ['email', 'first_name', 'last_name']);
    $this->variables = $this->defaultVariables;
    $this->recipients = [
      array_fill_keys($this->defaultVariables, '')
    ];

    // Initialize body to ensure buttons work
    $this->body = '';

    // Initialize selected sender if multiple senders enabled
    if (config('mass-mailer.multiple_senders', false)) {
        $senderModel = config('mass-mailer.sender_model');
        if ($senderModel && $senderModel::count() > 0) {
            $this->senders = $senderModel::all()->toArray();
        } else {
            $this->senders = config('mass-mailer.senders', []);
        }
        if (!empty($this->senders)) {
            $this->selectedSender = 0;
        }
    }
  }

  protected function rules()
  {
    $maxSize = config('mass-mailer.attachments.max_size', 10240) ;

    $rules = [
      'globalAttachments.*' => 'file|max:' . $maxSize,
      'perRecipientAttachments.*.*' => 'file|max:' . $maxSize,
      'subject' => 'required|string|max:255',
      'body' => 'required|string',
      'csvFile' => 'nullable|file|mimes:csv,txt|max:10240',
    ];

    // Add email validation for each recipient
    foreach ($this->recipients as $index => $recipient) {
      // Always add email validation if recipients exist
      $rules["recipients.{$index}.email"] = 'required|email';
    }

    return $rules;
  }

  // Alpine.js methods converted to Livewire actions
  public function addVariable()
  {
    $v = trim(strtolower($this->newVariable));
    $v = preg_replace('/[^a-z0-9_]/', '', $v);
    if ($v && !in_array($v, $this->variables)) {
      $this->variables[] = $v;
      $this->newVariable = '';
      foreach ($this->recipients as &$recipient) {
        $recipient[$v] = '';
      }
    }
  }

  public function deleteVariable($index)
  {
    if (isset($this->variables[$index])) {
      unset($this->variables[$index]);
      $this->variables = array_values($this->variables);
    }
  }

  public function addEmptyRecipient()
  {
    $newRecipient = array_fill_keys($this->variables, '');
    $this->recipients[] = $newRecipient;
    $this->perRecipientAttachments[] = [];
  }

  public function removeRecipient($index)
  {
    if (isset($this->recipients[$index])) {
      unset($this->recipients[$index]);
      unset($this->perRecipientAttachments[$index]);
      $this->recipients = array_values($this->recipients);
      $this->perRecipientAttachments = array_values($this->perRecipientAttachments);
    }
  }

  public function updatedCsvFile()
  {
    if ($this->csvFile) {
      $this->handleCSV();
    }
  }

  public function handleCSV()
  {
    if (!$this->csvFile) return;

    $path = $this->csvFile->getRealPath();
    $lines = file($path);

    if (empty($lines)) return;

    // Debug: Log raw file content
    Log::info('Raw CSV file content', ['lines' => $lines]);

    // Parse headers from first line
    $headerLine = trim($lines[0]);
    $headers = str_getcsv($headerLine);

    // Clean headers
    $cleanHeaders = [];
    foreach ($headers as $header) {
      $cleanHeader = trim(strtolower($header));
      $cleanHeader = str_replace('﻿', '', $cleanHeader); // Remove BOM
      if (!empty($cleanHeader) && !in_array($cleanHeader, ['#', 'actions'])) {
        $cleanHeaders[] = $cleanHeader;
      }
    }

    // Ensure email is in headers
    if (!in_array('email', $cleanHeaders)) {
      array_unshift($cleanHeaders, 'email');
    }

    $this->variables = $cleanHeaders;

    $parsedRecipients = [];
    $currentRecipient = null;

    // Process each line after header - standard CSV format
    for ($i = 1; $i < count($lines); $i++) {
      $line = trim($lines[$i]);

      // Skip empty lines
      if (empty($line)) {
        continue;
      }

      $row = str_getcsv($line);

      Log::info('Processing line', [
        'line_number' => $i,
        'line_content' => $line,
        'row' => $row
      ]);

      // Create new recipient for each row
      $recipient = array_fill_keys($cleanHeaders, '');

      // Fill fields based on CSV columns
      foreach ($cleanHeaders as $index => $fieldName) {
        if (isset($row[$index])) {
          $recipient[$fieldName] = trim($row[$index]);
        }
      }

      // Only add if we have at least an email
      if (!empty($recipient['email'])) {
        $parsedRecipients[] = $recipient;
        Log::info('Added recipient from CSV row', ['recipient' => $recipient]);
      }
    }

    // Save the last recipient
    if ($currentRecipient !== null && !empty($currentRecipient['email'])) {
      $parsedRecipients[] = $currentRecipient;
      Log::info('Saved last recipient', ['recipient' => $currentRecipient]);
    }

    // If no recipients found with special parsing, try standard CSV
    if (empty($parsedRecipients)) {
      Log::info('No recipients found with special parsing, trying standard CSV');
      $data = array_map('str_getcsv', $lines);

      for ($i = 1; $i < count($data); $i++) {
        $row = $data[$i];
        if (!empty(array_filter($row))) {
          $recipient = [];
          foreach ($cleanHeaders as $index => $header) {
            $recipient[$header] = trim($row[$index] ?? '');
          }
          if (!empty($recipient['email'])) {
            $parsedRecipients[] = $recipient;
          }
        }
      }
    }

    $this->recipients = $parsedRecipients;
    $this->perRecipientAttachments = array_fill(0, count($parsedRecipients), []);

    Log::info('CSV parsing complete', [
      'total_lines' => count($lines),
      'headers' => $cleanHeaders,
      'recipient_count' => count($parsedRecipients),
      'recipients' => $parsedRecipients
    ]);
  }

  public function insertVariable($variable)
  {
    if ($variable && $this->body !== null) {
      $this->body .= " {{ $variable }} ";
    }
  }

  public function previewMails()
  {
    if (empty($this->recipients) || !$this->subject || !$this->body) return;

    $recipient = $this->recipients[0];
    $this->previewEmail = $this->recipients[0]['email'] ?? '';
    $content = $this->body;
    $subj = $this->subject;

    foreach ($this->variables as $variable) {
      $value = $recipient[$variable] ?? '';
      $content = str_replace("{{ $variable }}", $value, $content);
      $subj = str_replace("{{ $variable }}", $value, $subj);
      $this->previewEmail = str_replace("{{ $variable }}", $value, $this->previewEmail);
    }

    $this->previewContent = $content;
    $this->showPreview = true;
  }

  public function closePreview()
  {
    $this->showPreview = false;
    $this->previewContent = '';
    $this->previewEmail = '';
  }

  public function openAttachmentModal($index)
  {
    Log::info('openAttachmentModal called', ['index' => $index, 'current_selectedRecipientIndex' => $this->selectedRecipientIndex]);
    $this->selectedRecipientIndex = $index;
    Log::info('selectedRecipientIndex set', ['selectedRecipientIndex' => $this->selectedRecipientIndex]);

    // Force re-render
    $this->dispatch('$refresh');
  }

  public function closeAttachmentModal()
  {
    $this->selectedRecipientIndex = null;
  }

  public function selectSender($index)
  {
    $this->selectedSender = $index;
     LivewireAlert::success()
          ->title($successConfig['title'] ?? 'Success!')
          ->text('Sender email changed to: ' . config('mass-mailer.senders')[$index]['name'])
          ->toast(true)->timer(3000)
          ->position('top-end')
          ->show();
  }

  public function removeAttachment($recipientIndex, $attachmentIndex)
  {
    if (isset($this->perRecipientAttachments[$recipientIndex][$attachmentIndex])) {
      unset($this->perRecipientAttachments[$recipientIndex][$attachmentIndex]);
      $this->perRecipientAttachments[$recipientIndex] = array_values($this->perRecipientAttachments[$recipientIndex]);
    }
  }

  public function clearForm()
  {
    $this->subject = '';
    $this->body = '';
    $this->recipients = [array_fill_keys($this->variables, '')];
    $this->variables = $this->defaultVariables;
    $this->globalAttachments = [];
    $this->perRecipientAttachments = [];
    $this->newVariable = '';
    $this->csvFile = null;
    $this->closePreview();
    $this->closeAttachmentModal();

    // Dispatch event to clear Quill editor
    $this->dispatch('clearMassMailForm');
  }

  public function sendMassMail()
  {
    // Debug: Log recipients data
    Log::info('SendMassMail called', [
      'recipient_count' => count($this->recipients),
      'recipients' => $this->recipients,
      'same_attachment' => $this->sameAttachmentForAll,
      'has_global_attachments' => !empty($this->globalAttachments),
      'per_recipient_attachments_count' => count($this->perRecipientAttachments)
    ]);

    // Validate first before processing files
    $this->validate();
    $this->sending = true;

    $storedGlobalAttachments = [];

    // Handle and store global attachments
    if ($this->sameAttachmentForAll && is_array($this->globalAttachments)) {
      foreach ($this->globalAttachments as $file) {
        if ($file) {
          // Store in public disk as configured
          $disk = config('mass-mailer.attachments.storage_disk', 'public');
          $path = $file->store('mass_mail/global', $disk);

          // Get the correct full path for the disk
          if ($disk === 'public') {
            $fullPath = storage_path('app/public/' . $path);
          } else {
            $fullPath = storage_path('app/' . $path);
          }

          Log::info('Stored global attachment', [
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'disk' => $disk,
            'full_path' => $fullPath,
            'file_exists' => file_exists($fullPath),
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 'N/A'
          ]);

          $storedGlobalAttachments[] = [
            'path' => $fullPath,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
          ];
        }
      }
    }

    // Handle and store per-recipient attachments
    $updatedPayload = [];
    foreach ($this->recipients as $i => $recipient) {
      $recipientAttachments = [];

      if (!$this->sameAttachmentForAll && isset($this->perRecipientAttachments[$i]) && is_array($this->perRecipientAttachments[$i])) {
        foreach ($this->perRecipientAttachments[$i] as $file) {
          if ($file) {
            // Store in public disk as configured
            $disk = config('mass-mailer.attachments.storage_disk', 'public');
            $path = $file->store("mass_mail/recipients/{$i}", $disk);

            // Get the correct full path for the disk
            if ($disk === 'public') {
              $fullPath = storage_path('app/public/' . $path);
            } else {
              $fullPath = storage_path('app/' . $path);
            }

            Log::info('Stored per-recipient attachment', [
              'recipient_index' => $i,
              'original_name' => $file->getClientOriginalName(),
              'stored_path' => $path,
              'disk' => $disk,
              'full_path' => $fullPath,
              'file_exists' => file_exists($fullPath),
              'file_size' => file_exists($fullPath) ? filesize($fullPath) : 'N/A'
            ]);

            $recipientAttachments[] = [
              'path' => $fullPath,
              'name' => $file->getClientOriginalName(),
              'mime' => $file->getClientMimeType(),
            ];
          }
        }
      }

      // Add attachments to the recipient data
      $recipient['attachments'] = $recipientAttachments;
      $updatedPayload[] = $recipient;
    }

    try {
      Log::info('Dispatching SendMassMailJob', [
        'recipient_count' => count($updatedPayload),
        'global_attachments_count' => count($storedGlobalAttachments),
        'same_attachment_for_all' => $this->sameAttachmentForAll,
        'first_recipient_attachments' => isset($updatedPayload[0]['attachments']) ? count($updatedPayload[0]['attachments']) : 0
      ]);

      // Get selected sender credentials
      $selectedSenderCredentials = null;
      if (config('mass-mailer.multiple_senders', false) && isset($this->senders[$this->selectedSender])) {
          $selectedSenderCredentials = $this->senders[$this->selectedSender];
      }

      // Dispatch the job with configured queue
      SendMassMailJob::dispatch(
        $updatedPayload,
        $this->subject,
        $this->body,
        $this->sameAttachmentForAll ? $storedGlobalAttachments : null,
        $this->sameAttachmentForAll,
        $selectedSenderCredentials
      )->onQueue(config('mass-mailer.queue.name', 'mass-mailer'));

      // Log the action if logging is enabled
      if (config('mass-mailer.logging.enabled', true)) {
        Log::info('Mass mail dispatched', [
          'recipient_count' => count($updatedPayload),
          'subject' => $this->subject,
          'has_attachments' => !empty($storedGlobalAttachments) || collect($updatedPayload)->pluck('attachments')->flatten()->isNotEmpty(),
        ]);
      }


      $successConfig = \mass_mailer_get_sweetalert_config('success');
      LivewireAlert::success()
          ->title($successConfig['title'] ?? 'Success!')
          ->text('Emails have been queued for sending!')
          ->withConfirmButton($successConfig['withConfirmButton'] ?? true)
          ->confirmButtonColor($successConfig['confirmButtonColor'] ?? '#31651e')
          ->confirmButtonText($successConfig['confirmButtonText'] ?? 'OK')
          ->show();

      // Reset form fields after successful sending
      $this->subject = '';
      $this->body = '';
      $this->recipients = [
        array_fill_keys($this->defaultVariables, '')
      ];
      $this->globalAttachments = [];
      $this->perRecipientAttachments = [];

      $this->sending = false;
      $this->clearForm();
    } catch (\Exception $e) {
      Log::error('Failed to dispatch mass mail job', [
        'error' => $e->getMessage(),
        'recipient_count' => count($updatedPayload),
      ]);

      $errorConfig = \mass_mailer_get_sweetalert_config('error');
      LivewireAlert::error()
          ->title($errorConfig['title'] ?? 'Error!')
          ->text('Failed to send emails. Please try again.')
          ->show();
  } finally {
      $this->sending = false;
    }
  }


  public function render()
  {
    $framework = config('mass-mailer.ui.framework', 'bootstrap');
    $viewName = 'mass-mailer::' . $framework . '.mass-mailer';

    Log::info('Rendering MassMailer component', [
      'framework' => $framework,
      'sameAttachmentForAll' => $this->sameAttachmentForAll,
      'selectedRecipientIndex' => $this->selectedRecipientIndex,
      'recipients_count' => count($this->recipients),
      'perRecipientAttachments_count' => count($this->perRecipientAttachments)
    ]);

    return view($viewName, [
      'variables' => $this->variables,
      'newVariable' => $this->newVariable,
      'previewContent' => $this->previewContent,
      'showPreview' => $this->showPreview,
      'previewEmail' => $this->previewEmail,
      'selectedRecipientIndex' => $this->selectedRecipientIndex,
      'selectedSender' => $this->selectedSender,
    ]);
  }
}
