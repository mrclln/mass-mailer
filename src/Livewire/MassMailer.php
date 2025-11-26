<?php

namespace Mrclln\MassMailer\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Mrclln\MassMailer\Jobs\SendMassMailJob;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Mrclln\MassMailer\Services\AttachmentService;
use Mrclln\MassMailer\Services\CsvService;
use Mrclln\MassMailer\Services\SenderService;
use Mrclln\MassMailer\Services\RecipientService;
use Mrclln\MassMailer\Services\EmailTemplateService;

class MassMailer extends Component
{
  use WithFileUploads;

  protected AttachmentService $attachmentService;
  protected CsvService $csvService;
  protected SenderService $senderService;
  protected RecipientService $recipientService;
  protected EmailTemplateService $emailTemplateService;

  public function boot()
  {
      $this->attachmentService = app(AttachmentService::class);
      $this->csvService = app(CsvService::class);
      $this->senderService = app(SenderService::class);
      $this->recipientService = app(RecipientService::class);
      $this->emailTemplateService = app(EmailTemplateService::class);
  }

  public array $recipients = [];
  public array $globalAttachments = [];
  public array $perRecipientAttachments = [];
  public $subject;
  public $body;
  public $sameAttachmentForAll = true;
  public $useAttachmentPaths = false;
  public $attachmentFolderName = '';
  public $attachmentFiles = [];

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
  public $selectedSenderId = null;
  public $showAddSenderForm = false;

  // New sender form fields
  public $newSenderName = '';
  public $newSenderEmail = '';
  public $newSenderHost = '';
  public $newSenderPort = 587;
  public $newSenderUsername = '';
  public $newSenderPassword = '';
  public $newSenderEncryption = 'tls';

  public function mount() :void
  {
      $this->attachmentFolderName = 'folder_' . now()->format('Y-m-d_H-i-s') . '_' . uniqid();

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
        $this->senders = $this->senderService->getAllSenders();

        if (!empty($this->senders)) {
            $this->selectedSender = 0;
            $this->selectedSenderId = $this->senders[0]['id'];
        }
    }
  }

  protected function rules()
  {
    $maxSize = config('mass-mailer.attachments.max_size', 10240) ;

    $rules = [
      'globalAttachments.*' => 'file|max:' . $maxSize,
      'perRecipientAttachments.*.*' => 'file|max:' . $maxSize,
      'attachmentFiles.*' => 'file|max:' . $maxSize,
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

  // Variable management methods (delegated to RecipientService)
  public function addVariable()
  {
    $this->variables = $this->recipientService->addVariable($this->newVariable, $this->variables);
    $this->newVariable = '';

    // Update recipients with new variable
    foreach ($this->recipients as &$recipient) {
      $recipient[trim(strtolower(preg_replace('/[^a-z0-9_]/', '', $this->newVariable)))] = '';
    }
  }

  public function deleteVariable($index)
  {
    $this->variables = $this->recipientService->deleteVariable($this->variables, $index);
  }

  // Recipient management methods (delegated to RecipientService)
  public function addEmptyRecipient()
  {
    $newRecipient = $this->recipientService->addEmptyRecipient($this->variables);
    $this->recipients[] = $newRecipient;

    // Only initialize perRecipientAttachments if not using attachment paths
    if (!$this->useAttachmentPaths) {
      $this->perRecipientAttachments[] = [];
    }
  }

  public function removeRecipient($index)
  {
    $this->recipients = $this->recipientService->removeRecipient($this->recipients, $index);

    if (isset($this->perRecipientAttachments[$index])) {
      unset($this->perRecipientAttachments[$index]);
      $this->perRecipientAttachments = array_values($this->perRecipientAttachments);
    }
  }

  // CSV handling method (delegated to CsvService)
  public function updatedCsvFile()
  {
    Log::info('CSV file property updated', [
      'csv_file_exists' => isset($this->csvFile),
      'csv_file_type' => isset($this->csvFile) ? get_class($this->csvFile) : 'null',
      'file_name' => isset($this->csvFile) ? $this->csvFile->getClientOriginalName() : 'null',
      'file_size' => isset($this->csvFile) ? $this->csvFile->getSize() : 'null'
    ]);

    if ($this->csvFile) {
      Log::info('CSV file processing started', [
        'file_name' => $this->csvFile->getClientOriginalName(),
        'file_size' => $this->csvFile->getSize(),
        'default_variables' => $this->defaultVariables
      ]);

      $csvResult = $this->csvService->handleCSV($this->csvFile, $this->defaultVariables);

      Log::info('CSV processing result', [
        'success' => $csvResult['success'] ?? false,
        'variables' => $csvResult['variables'] ?? [],
        'recipients_count' => count($csvResult['recipients'] ?? []),
        'recipients' => $csvResult['recipients'] ?? []
      ]);

      if ($csvResult['success']) {
        $this->variables = $csvResult['variables'];
        $this->recipients = $csvResult['recipients'];
        $this->perRecipientAttachments = array_fill(0, count($csvResult['recipients']), []);

        Log::info('CSV data applied to component', [
          'variables_updated' => $this->variables,
          'recipients_updated' => $this->recipients,
          'recipients_count' => count($this->recipients),
          'perRecipientAttachments_count' => is_countable($this->perRecipientAttachments) ? count($this->perRecipientAttachments) : 0
        ]);
      } else {
        Log::error('CSV processing failed', [
          'error' => $csvResult['error'] ?? 'Unknown error'
        ]);
      }
    } else {
      Log::warning('No CSV file found in updatedCsvFile method');
    }
  }

  // Attachment path handling (delegated to AttachmentService and RecipientService)
  public function updatedUseAttachmentPaths($value)
  {
    if ($value) {
      // When using attachment paths, disable same attachment for all
      $this->sameAttachmentForAll = false;

      // Create the folder structure: mass_mail/{user_id}/{folder_name}
      $this->attachmentService->createAttachmentFolder($this->attachmentFolderName);

      // Handle variable changes
      $result = $this->recipientService->handleAttachmentPathVariableChange(true, $this->variables, $this->recipients);
      $this->variables = $result['variables'];
      $this->recipients = $result['recipients'];

    } else {
      // Handle variable changes when disabling attachment paths
      $result = $this->recipientService->handleAttachmentPathVariableChange(false, $this->variables, $this->recipients);
      $this->variables = $result['variables'];
      $this->recipients = $result['recipients'];

      // Clear attachment folder data
      $this->attachmentFolderName = '';
      $this->attachmentFiles = [];
    }
  }

  /**
   * Handle sameAttachmentForAll checkbox changes
   */
  public function updatedSameAttachmentForAll($value)
  {
    if (!$value) {
      // When unchecking "same attachment for all", initialize perRecipientAttachments for existing recipients
      $this->perRecipientAttachments = array_fill(0, count($this->recipients), []);
    }
  }

  // Attachment methods (delegated to AttachmentService)

  /**
   * Remove an uploaded attachment file
   */
  public function removeUploadedAttachment($index)
  {
    if (isset($this->attachmentFiles[$index])) {
      unset($this->attachmentFiles[$index]);
      $this->attachmentFiles = array_values($this->attachmentFiles);
    }
  }

  /**
   * Updated handler for attachmentFiles to automatically save to folder
   */
  public function updatedAttachmentFiles()
  {
    if ($this->useAttachmentPaths && !empty($this->attachmentFiles)) {
      $this->attachmentService->saveAttachmentFilesToFolder($this->attachmentFiles, $this->attachmentFolderName);
    }
  }

  // CSV handling is now delegated to CsvService in updatedCsvFile()

  // Email template methods (delegated to EmailTemplateService)
  public function insertVariable($variable)
  {
    $this->body = $this->emailTemplateService->insertVariable($variable, $this->body);
  }

  public function previewMails()
  {
    $result = $this->emailTemplateService->previewMails($this->recipients, $this->subject, $this->body, $this->variables);

    if ($result['success']) {
      $this->previewEmail = $result['previewEmail'];
      $this->previewContent = $result['previewContent'];
      $this->showPreview = true;
    }
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

  public function selectSender($senderId)
  {
    if ($senderId === 'add-new') {
      $this->setShowAddSenderForm(true);
      return;
    }

    $this->selectedSenderId = $senderId;

    // Find the index of the selected sender
    $index = array_search($senderId, array_column($this->senders, 'id'));
    if ($index !== false) {
      $this->selectedSender = $index;

      // Log the selected sender for debugging
      Log::info('Sender selected', [
        'sender_id' => $senderId,
        'sender_index' => $index,
        'sender_data' => $this->senders[$index]
      ]);

      $successConfig = \mass_mailer_get_sweetalert_config('success');
      LivewireAlert::success()
          ->title($successConfig['title'] ?? 'Success!')
          ->text('Sender email changed to: ' . $this->senders[$index]['name'] . ' (' . $this->senders[$index]['email'] . ')')
          ->toast(true)->timer(3000)
          ->position('top-end')
          ->show();
    }
  }

  public function setShowAddSenderForm($value)
  {
    $this->showAddSenderForm = $value;
    if ($value) {
      // Clear selected sender when opening add sender form
      $this->selectedSenderId = null;
      $this->selectedSender = null;
    }
  }

  public function updatedSelectedSenderId($value)
  {
    if ($value) {
      $this->selectSender($value);
    }
  }

  public function removeAttachment($recipientIndex, $attachmentIndex)
  {
    if (isset($this->perRecipientAttachments[$recipientIndex][$attachmentIndex])) {
      unset($this->perRecipientAttachments[$recipientIndex][$attachmentIndex]);
      $this->perRecipientAttachments[$recipientIndex] = array_values($this->perRecipientAttachments[$recipientIndex]);
    }
  }

  // Attachment processing methods moved to AttachmentService
  // CC email processing methods moved to CsvService

  public function clearForm()
  {
    $this->subject = '';
    $this->body = '';
    $this->recipients = [array_fill_keys($this->variables, '')];
    $this->variables = $this->defaultVariables;
    $this->globalAttachments = [];
    $this->perRecipientAttachments = [];
    $this->useAttachmentPaths = false;
    $this->sameAttachmentForAll = true;
    $this->attachmentFolderName = '';
    $this->attachmentFiles = [];
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
      'per_recipient_attachments_count' => is_countable($this->perRecipientAttachments) ? count($this->perRecipientAttachments) : 0
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

      // Handle attachment paths from recipients table when useAttachmentPaths is enabled
      if ($this->useAttachmentPaths && isset($recipient['attachments']) && !empty($recipient['attachments'])) {
        // Split comma-separated file names
        $fileNames = array_map('trim', explode(',', $recipient['attachments']));
        $recipientAttachments = array_merge($recipientAttachments, $this->attachmentService->processAttachmentFilesFromFolder($fileNames, $i, $this->attachmentFolderName));

        Log::info('Processed attachment files from folder', [
          'recipient_index' => $i,
          'attachments_value' => $recipient['attachments'],
          'processed_attachments' => $recipientAttachments
        ]);
      }

      // Handle auto-detected attachments from CSV
      if (isset($recipient['_auto_attachments']) && is_array($recipient['_auto_attachments'])) {
        foreach ($recipient['_auto_attachments'] as $autoAttachment) {
          if (isset($autoAttachment['path']) && file_exists($autoAttachment['path'])) {
            $recipientAttachments[] = [
              'path' => $autoAttachment['path'],
              'name' => $autoAttachment['name'],
              'mime' => $autoAttachment['mime'],
              'auto_detected' => true
            ];

            Log::info('Added auto-detected attachment', [
              'recipient_index' => $i,
              'file_path' => $autoAttachment['path'],
              'file_name' => $autoAttachment['name'],
              'auto_detected' => true
            ]);
          }
        }
        // Remove the temporary _auto_attachments field
        unset($recipient['_auto_attachments']);
      }

      // Handle manual per-recipient attachments (uploaded files) - only if not using attachment paths
      if (!$this->useAttachmentPaths && !$this->sameAttachmentForAll && isset($this->perRecipientAttachments[$i]) && is_array($this->perRecipientAttachments[$i])) {
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
              'file_size' => file_exists($fullPath) ? filesize($fullPath) : 'N/A',
              'auto_detected' => false
            ]);

            $recipientAttachments[] = [
              'path' => $fullPath,
              'name' => $file->getClientOriginalName(),
              'mime' => $file->getClientMimeType(),
              'auto_detected' => false
            ];
          }
        }
      }

      // Add attachments and CC emails to the recipient data
      $recipient['attachments'] = $recipientAttachments;
      $recipient['_auto_cc'] = $recipient['_auto_cc'] ?? [];
      $updatedPayload[] = $recipient;
    }

    try {
      Log::info('Dispatching SendMassMailJob', [
        'recipient_count' => count($updatedPayload),
        'global_attachments_count' => is_countable($storedGlobalAttachments) ? count($storedGlobalAttachments) : 0,
        'same_attachment_for_all' => $this->sameAttachmentForAll,
        'first_recipient_attachments' => isset($updatedPayload[0]['attachments']) ? count($updatedPayload[0]['attachments']) : 0
      ]);

      // Get selected sender credentials
      $selectedSenderCredentials = null;
      if (config('mass-mailer.multiple_senders', false) && $this->selectedSenderId) {
          $selectedSenderCredentials = $this->senderService->getSelectedSenderCredentials($this->senders, $this->selectedSenderId);

          if ($selectedSenderCredentials) {
              $validation = $this->senderService->validateSenderCredentials($selectedSenderCredentials);

              if (!$validation['valid']) {
                  $errorConfig = \mass_mailer_get_sweetalert_config('error');
                  LivewireAlert::error()
                      ->title($errorConfig['title'] ?? 'Configuration Error!')
                      ->text($validation['error_message'])
                      ->show();

                  $this->sending = false;
                  return;
              }

              // Log the selected sender credentials for debugging
              Log::info('Selected sender credentials prepared', [
                  'sender_id' => $this->selectedSenderId,
                  'sender_name' => $selectedSenderCredentials['name'],
                  'sender_email' => $selectedSenderCredentials['email'],
                  'is_config_sender' => str_starts_with($this->selectedSenderId, 'config_')
              ]);
          }
      }



      // Dispatch the job with configured queue
      SendMassMailJob::dispatch(
        $updatedPayload,
        $this->subject,
        $this->body,
        $this->sameAttachmentForAll ? $storedGlobalAttachments : null,
        $this->sameAttachmentForAll,
        $selectedSenderCredentials,
        auth()->id()
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

  public function closeAddSenderForm()
  {
    $this->setShowAddSenderForm(false);
    $this->reset(['newSenderName', 'newSenderEmail', 'newSenderHost', 'newSenderPort', 'newSenderUsername', 'newSenderPassword']);
    $this->newSenderEncryption = 'tls';
  }

  // Sender management methods (delegated to SenderService)
  public function reloadSenders()
  {
    if (!config('mass-mailer.multiple_senders', false)) {
      return;
    }

    $this->senders = $this->senderService->getAllSenders();
  }

  public function saveNewSender()
  {
    $this->validate([
      'newSenderName' => 'required|string|max:255',
      'newSenderEmail' => 'required|email|unique:mass_mailer_senders,email',
      'newSenderHost' => 'required|string|max:255',
      'newSenderPort' => 'required|integer|min:1|max:65535',
      'newSenderUsername' => 'required|string|max:255',
      'newSenderPassword' => 'required|string',
      'newSenderEncryption' => 'required|string|in:tls,ssl',
    ]);

    // Test the SMTP credentials before saving
    if (!$this->senderService->testSenderCredentials(
        $this->newSenderHost,
        $this->newSenderPort,
        $this->newSenderUsername,
        $this->newSenderPassword,
        $this->newSenderEncryption,
        $this->newSenderEmail,
        $this->newSenderName
    )) {
      $errorConfig = \mass_mailer_get_sweetalert_config('error');
      LivewireAlert::error()
          ->title($errorConfig['title'] ?? 'Invalid Credentials!')
          ->text('The SMTP credentials are invalid. Please check your settings and try again.')
          ->show();
      return;
    }

    try {
      $newSender = $this->senderService->saveNewSender([
        'name' => $this->newSenderName,
        'email' => $this->newSenderEmail,
        'host' => $this->newSenderHost,
        'port' => $this->newSenderPort,
        'username' => $this->newSenderUsername,
        'password' => $this->newSenderPassword,
        'encryption' => $this->newSenderEncryption,
      ]);

      // Reload both config-based and database senders, then select the new one
      $this->reloadSenders();
      $this->selectedSenderId = $newSender->id;
      $this->selectedSender = array_search($newSender->id, array_column($this->senders, 'id'));

      $this->closeAddSenderForm();

      $successConfig = \mass_mailer_get_sweetalert_config('success');
      LivewireAlert::success()
          ->title($successConfig['title'] ?? 'Success!')
          ->text('New sender added successfully!')
          ->toast(true)->timer(3000)
          ->position('top-end')
          ->show();

    } catch (\Exception $e) {
      Log::error('Failed to save new sender', [
        'error' => $e->getMessage(),
        'email' => $this->newSenderEmail
      ]);

      $errorConfig = \mass_mailer_get_sweetalert_config('error');
      LivewireAlert::error()
          ->title($errorConfig['title'] ?? 'Error!')
          ->text('Failed to save new sender. Please try again.')
          ->show();
    }
  }

  // testSenderCredentials method moved to SenderService


  public function render()
  {
    $framework = config('mass-mailer.ui.framework', 'bootstrap');
    $viewName = 'mass-mailer::' . $framework . '.mass-mailer';

    Log::info('Rendering MassMailer component', [
      'framework' => $framework,
      'sameAttachmentForAll' => $this->sameAttachmentForAll,
      'selectedRecipientIndex' => $this->selectedRecipientIndex,
      'recipients_count' => count($this->recipients),
      'perRecipientAttachments_count' => is_countable($this->perRecipientAttachments) ? count($this->perRecipientAttachments) : 0
    ]);

    return view($viewName, [
      'variables' => $this->variables,
      'newVariable' => $this->newVariable,
      'previewContent' => $this->previewContent,
      'showPreview' => $this->showPreview,
      'previewEmail' => $this->previewEmail,
      'selectedRecipientIndex' => $this->selectedRecipientIndex,
      'selectedSender' => $this->selectedSender,
      'selectedSenderId' => $this->selectedSenderId,
      'showAddSenderForm' => $this->showAddSenderForm,
      'useAttachmentPaths' => $this->useAttachmentPaths,
      'attachmentFolderName' => $this->attachmentFolderName,
      'attachmentFiles' => $this->attachmentFiles,
    ]);
  }
}
