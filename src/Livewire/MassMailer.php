<?php

namespace Mrclln\MassMailer\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Mrclln\MassMailer\Jobs\SendMassMailJob;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $this->senders = [];

        // Load config-based senders first
        $configSenders = config('mass-mailer.senders', []);
        foreach ($configSenders as $index => $sender) {
            // Assign a negative index for config senders to avoid conflicts with DB IDs
            $this->senders[] = array_merge($sender, ['id' => 'config_' . $index]);
        }

        // Append database-based senders
        $senderModel = config('mass-mailer.sender_model');
        if ($senderModel && $senderModel::count() > 0) {
            $dbSenders = $senderModel::all()->toArray();
            $this->senders = array_merge($this->senders, $dbSenders);
        }

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

    // Check for attachments and CC columns
    $hasAttachmentsColumn = in_array('attachments', $cleanHeaders);
    $hasCcColumn = in_array('cc', $cleanHeaders);

    $attachmentColumnIndex = null;
    $ccColumnIndex = null;

    if ($hasAttachmentsColumn) {
      $attachmentColumnIndex = array_search('attachments', $cleanHeaders);
      Log::info('Attachments column detected', ['index' => $attachmentColumnIndex]);
    }

    if ($hasCcColumn) {
      $ccColumnIndex = array_search('cc', $cleanHeaders);
      Log::info('CC column detected', ['index' => $ccColumnIndex]);
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
      $attachmentPaths = [];
      $ccEmails = [];

      // Fill fields based on CSV columns
      foreach ($cleanHeaders as $index => $fieldName) {
        if (isset($row[$index])) {
          $value = trim($row[$index]);

          // Process attachments column
          if ($fieldName === 'attachments' && !empty($value)) {
            // Split comma-separated file paths
            $filePaths = array_map('trim', explode(',', $value));
            $recipient[$fieldName] = $value; // Store original value for template variables
            $attachmentPaths = $this->processAttachmentPaths($filePaths, $i);
            Log::info('Processed attachment paths', [
              'recipient_index' => $i - 1,
              'original_value' => $value,
              'file_paths' => $filePaths,
              'processed_attachments' => $attachmentPaths
            ]);
          }
          // Process CC column
          elseif ($fieldName === 'cc' && !empty($value)) {
            // Split comma-separated email addresses
            $emailAddresses = array_map('trim', explode(',', $value));
            $recipient[$fieldName] = $value; // Store original value for template variables
            $ccEmails = $this->processCcEmails($emailAddresses, $i);
            Log::info('Processed CC emails', [
              'recipient_index' => $i - 1,
              'original_value' => $value,
              'email_addresses' => $emailAddresses,
              'processed_cc' => $ccEmails
            ]);
          }
          else {
            $recipient[$fieldName] = $value;
          }
        }
      }

      // Only add if we have at least an email
      if (!empty($recipient['email'])) {
        // Store attachment paths and CC emails in separate properties for processing
        $recipient['_auto_attachments'] = $attachmentPaths;
        $recipient['_auto_cc'] = $ccEmails;
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
          $attachmentPaths = [];
          $ccEmails = [];
          foreach ($cleanHeaders as $index => $header) {
            $value = trim($row[$index] ?? '');
            if ($header === 'attachments' && !empty($value)) {
              $filePaths = array_map('trim', explode(',', $value));
              $recipient[$header] = $value; // Store original value for template variables
              $attachmentPaths = $this->processAttachmentPaths($filePaths, $i - 1);
            }
            elseif ($header === 'cc' && !empty($value)) {
              $emailAddresses = array_map('trim', explode(',', $value));
              $recipient[$header] = $value; // Store original value for template variables
              $ccEmails = $this->processCcEmails($emailAddresses, $i - 1);
            }
            else {
              $recipient[$header] = $value;
            }
          }
          if (!empty($recipient['email'])) {
            $recipient['_auto_attachments'] = $attachmentPaths;
            $recipient['_auto_cc'] = $ccEmails;
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
      'has_attachments_column' => $hasAttachmentsColumn,
      'has_cc_column' => $hasCcColumn,
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

  /**
   * Process attachment file paths from CSV data.
   *
   * @param array $filePaths Array of file paths from CSV
   * @param int $recipientIndex Index of the recipient for logging
   * @return array Processed attachment data
   */
  protected function processAttachmentPaths(array $filePaths, int $recipientIndex): array
  {
    $processedAttachments = [];

    foreach ($filePaths as $filePath) {
      if (empty($filePath)) continue;

      // Clean the file path
      $filePath = trim($filePath);

      // Check if file exists
      if (!file_exists($filePath)) {
        Log::warning('Attachment file not found', [
          'recipient_index' => $recipientIndex,
          'file_path' => $filePath,
          'exists' => false
        ]);

        // Try to upload the file if auto-upload is enabled and file is accessible
        if (config('mass-mailer.attachments.auto_upload_from_csv', true)) {
          $uploadedFilePath = $this->tryUploadAttachmentFile($filePath, $recipientIndex);
          if ($uploadedFilePath) {
            $filePath = $uploadedFilePath;
          } else {
            continue;
          }
        } else {
          continue;
        }
      }

      // Get file information
      $fileName = basename($filePath);
      $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
      $fileSize = filesize($filePath);

      // Check file size (if configured)
      $maxSize = config('mass-mailer.attachments.max_size', 10240) * 1024; // Convert KB to bytes
      if ($fileSize > $maxSize) {
        Log::warning('Attachment file too large', [
          'recipient_index' => $recipientIndex,
          'file_path' => $filePath,
          'file_size' => $fileSize,
          'max_size' => $maxSize
        ]);
        continue;
      }

      $processedAttachments[] = [
        'path' => $filePath,
        'name' => $fileName,
        'mime' => $mimeType,
        'original_path' => $filePath, // Keep original for reference
        'auto_detected' => true,
        'uploaded' => isset($uploadedFilePath) && $uploadedFilePath !== $filePath
      ];

      Log::info('Processed auto-detected attachment', [
        'recipient_index' => $recipientIndex,
        'file_path' => $filePath,
        'file_name' => $fileName,
        'mime_type' => $mimeType,
        'file_size' => $fileSize,
        'was_uploaded' => isset($uploadedFilePath) && $uploadedFilePath !== $filePath
      ]);
    }

    return $processedAttachments;
  }

  /**
   * Try to upload an attachment file from a local path to the server.
   * This handles cases where CSV contains paths that might be accessible
   * via network drives or shared folders.
   *
   * @param string $filePath The original file path from CSV
   * @param int $recipientIndex Index of the recipient for logging
   * @return string|null The server-side path if successful, null otherwise
   */
  protected function tryUploadAttachmentFile(string $filePath, int $recipientIndex): ?string
  {
    try {
      // Check if file exists locally (might be network drive or shared folder)
      if (!file_exists($filePath)) {
        Log::info('File not accessible for upload', [
          'recipient_index' => $recipientIndex,
          'file_path' => $filePath,
          'accessible' => false
        ]);
        return null;
      }

      // Check if we can read the file
      if (!is_readable($filePath)) {
        Log::warning('File exists but is not readable', [
          'recipient_index' => $recipientIndex,
          'file_path' => $filePath
        ]);
        return null;
      }

      // Read file content
      $fileContent = file_get_contents($filePath);
      if ($fileContent === false) {
        Log::warning('Failed to read file content', [
          'recipient_index' => $recipientIndex,
          'file_path' => $filePath
        ]);
        return null;
      }

      // Generate unique filename for temporary storage
      $fileName = basename($filePath);
      $extension = pathinfo($fileName, PATHINFO_EXTENSION);
      $baseName = pathinfo($fileName, PATHINFO_FILENAME);
      $uniqueName = $baseName . '_' . uniqid() . '.' . $extension;

      // Create temporary storage directory
      $tempDir = storage_path('app/temp_attachments');
      if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
      }

      $tempFilePath = $tempDir . '/' . $uniqueName;

      // Save file to temporary location
      if (file_put_contents($tempFilePath, $fileContent) === false) {
        Log::warning('Failed to save uploaded file', [
          'recipient_index' => $recipientIndex,
          'file_path' => $filePath,
          'temp_path' => $tempFilePath
        ]);
        return null;
      }

      Log::info('Successfully uploaded attachment file', [
        'recipient_index' => $recipientIndex,
        'original_path' => $filePath,
        'uploaded_path' => $tempFilePath,
        'file_size' => filesize($tempFilePath)
      ]);

      return $tempFilePath;

    } catch (\Exception $e) {
      Log::error('Exception during file upload', [
        'recipient_index' => $recipientIndex,
        'file_path' => $filePath,
        'error' => $e->getMessage()
      ]);
      return null;
    }
  }

  /**
   * Process CC email addresses from CSV data.
   *
   * @param array $emailAddresses Array of email addresses from CSV
   * @param int $recipientIndex Index of the recipient for logging
   * @return array Processed CC email data
   */
  protected function processCcEmails(array $emailAddresses, int $recipientIndex): array
  {
    $processedCc = [];

    foreach ($emailAddresses as $email) {
      if (empty($email)) continue;

      // Clean the email address
      $email = trim($email);

      // Validate email format
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Log::warning('Invalid CC email address', [
          'recipient_index' => $recipientIndex,
          'email' => $email,
          'valid' => false
        ]);
        continue;
      }

      $processedCc[] = [
        'email' => $email,
        'auto_detected' => true
      ];

      Log::info('Processed CC email', [
        'recipient_index' => $recipientIndex,
        'email' => $email,
        'auto_detected' => true
      ]);
    }

    return $processedCc;
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

      // Handle manual per-recipient attachments (uploaded files)
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
        'global_attachments_count' => count($storedGlobalAttachments),
        'same_attachment_for_all' => $this->sameAttachmentForAll,
        'first_recipient_attachments' => isset($updatedPayload[0]['attachments']) ? count($updatedPayload[0]['attachments']) : 0
      ]);

      // Get selected sender credentials
      $selectedSenderCredentials = null;
      if (config('mass-mailer.multiple_senders', false) && $this->selectedSenderId) {
          foreach ($this->senders as $sender) {
              if (($sender['id'] ?? null) == $this->selectedSenderId) {
                  // For database senders, use the full sender data
                  if (str_starts_with($this->selectedSenderId, 'config_')) {
                      // For config senders, we need to get SMTP credentials from config
                      $configSenders = config('mass-mailer.senders', []);
                      $configIndex = (int) str_replace('config_', '', $this->selectedSenderId);
                      if (isset($configSenders[$configIndex])) {
                          $configSender = $configSenders[$configIndex];
                          // Use the sender's own credentials from config, not the default mail config
                          $selectedSenderCredentials = [
                              'name' => $configSender['name'],
                              'email' => $configSender['email'],
                              'host' => $configSender['host'] ?? config('mail.mailers.smtp.host', 'smtp.gmail.com'),
                              'port' => $configSender['port'] ?? config('mail.mailers.smtp.port', 587),
                              'username' => $configSender['username'] ?? $configSender['email'],
                              'password' => $configSender['password'] ?? '',
                              'encryption' => $configSender['encryption'] ?? config('mail.mailers.smtp.encryption', 'tls'),
                          ];
                      }
                  } else {
                      // For database senders, use the sender data directly
                      $selectedSenderCredentials = [
                          'name' => $sender['name'],
                          'email' => $sender['email'],
                          'host' => $sender['host'],
                          'port' => $sender['port'],
                          'username' => $sender['username'],
                          'password' => $sender['password'],
                          'encryption' => $sender['encryption'],
                      ];
                  }

                  // Validate that all required sender credentials are present and not empty
                  $requiredKeys = ['host', 'port', 'username', 'password', 'encryption'];
                  $missingKeys = [];
                  $emptyKeys = [];

                  foreach ($requiredKeys as $key) {
                      if (!isset($selectedSenderCredentials[$key])) {
                          $missingKeys[] = $key;
                      } elseif (empty($selectedSenderCredentials[$key])) {
                          $emptyKeys[] = $key;
                      }
                  }

                  if (!empty($missingKeys) || !empty($emptyKeys)) {
                      $errorMessage = 'Sender credentials are incomplete. ';
                      if (!empty($missingKeys)) {
                          $errorMessage .= 'Missing keys: ' . implode(', ', $missingKeys) . '. ';
                      }
                      if (!empty($emptyKeys)) {
                          $errorMessage .= 'Empty keys: ' . implode(', ', $emptyKeys) . '. ';
                      }
                      $errorMessage .= 'Please check the sender configuration.';

                      Log::error('Sender credentials validation failed', [
                          'sender_id' => $this->selectedSenderId,
                          'missing_keys' => $missingKeys,
                          'empty_keys' => $emptyKeys,
                          'credentials' => $selectedSenderCredentials
                      ]);

                      $errorConfig = \mass_mailer_get_sweetalert_config('error');
                      LivewireAlert::error()
                          ->title($errorConfig['title'] ?? 'Configuration Error!')
                          ->text($errorMessage)
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

                  break;
              }
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

  public function reloadSenders()
  {
    if (!config('mass-mailer.multiple_senders', false)) {
      return;
    }

    $this->senders = [];

    // Load config-based senders first
    $configSenders = config('mass-mailer.senders', []);
    foreach ($configSenders as $index => $sender) {
      // Assign a negative index for config senders to avoid conflicts with DB IDs
      $this->senders[] = array_merge($sender, ['id' => 'config_' . $index]);
    }

    // Append database-based senders
    $senderModel = config('mass-mailer.sender_model');
    if ($senderModel && $senderModel::count() > 0) {
      $dbSenders = $senderModel::all()->toArray();
      $this->senders = array_merge($this->senders, $dbSenders);
    }
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
    if (!$this->testSenderCredentials()) {
      $errorConfig = \mass_mailer_get_sweetalert_config('error');
      LivewireAlert::error()
          ->title($errorConfig['title'] ?? 'Invalid Credentials!')
          ->text('The SMTP credentials are invalid. Please check your settings and try again.')
          ->show();
      return;
    }

    try {
      $senderModel = config('mass-mailer.sender_model', \Mrclln\MassMailer\Models\MassMailerSender::class);

      $newSender = $senderModel::create([
        'name' => $this->newSenderName,
        'email' => $this->newSenderEmail,
        'host' => $this->newSenderHost,
        'port' => $this->newSenderPort,
        'username' => $this->newSenderUsername,
        'password' => $this->newSenderPassword,
        'encryption' => $this->newSenderEncryption,
        'user_id' => auth()->id(),
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

  /**
   * Test the SMTP credentials by sending a test email to the sender's own email address
   */
  protected function testSenderCredentials(): bool
  {
    try {
      // Clear any cached mail configuration first
      app()->forgetInstance('mail.manager');
      app()->forgetInstance('mailer');

      // Configure temporary SMTP settings for testing
      $tempConfig = [
        'host' => $this->newSenderHost,
        'port' => $this->newSenderPort,
        'username' => $this->newSenderUsername,
        'password' => $this->newSenderPassword,
        'encryption' => $this->newSenderEncryption,
        'transport' => 'smtp',
      ];

      // Set the temporary configuration
      config(['mail.mailers.smtp' => $tempConfig]);
      config(['mail.default' => 'smtp']);

      // Set the from address to match the sender
      config([
        'mail.from.address' => $this->newSenderEmail,
        'mail.from.name' => $this->newSenderName
      ]);

      // Create a test email
      $testSubject = 'Mass Mailer - Test Email';
      $testBody = '
        <html>
          <body>
            <h2>Email Configuration Test</h2>
            <p>This is a test email to verify your SMTP configuration for the Mass Mailer application.</p>
            <p><strong>Sender:</strong> ' . htmlspecialchars($this->newSenderName) . ' (' . htmlspecialchars($this->newSenderEmail) . ')</p>
            <p><strong>SMTP Server:</strong> ' . htmlspecialchars($this->newSenderHost) . ':' . $this->newSenderPort . ' (' . strtoupper($this->newSenderEncryption) . ')</p>
            <p><strong>Timestamp:</strong> ' . now()->toDateTimeString() . '</p>
            <p>If you received this email, your SMTP configuration is working correctly!</p>
          </body>
        </html>
      ';

      // Try to send the test email
      Mail::html($testBody, function ($message) {
        $message->to($this->newSenderEmail, $this->newSenderName)
                ->subject('Mass Mailer - Test Email')
                ->from($this->newSenderEmail, $this->newSenderName);
      });

      Log::info('Test email sent successfully', [
        'email' => $this->newSenderEmail,
        'host' => $this->newSenderHost,
        'port' => $this->newSenderPort,
        'encryption' => $this->newSenderEncryption
      ]);

      return true;

    } catch (\Swift_TransportException $e) {
      Log::error('SMTP transport error during sender credential test', [
        'email' => $this->newSenderEmail,
        'host' => $this->newSenderHost,
        'port' => $this->newSenderPort,
        'error' => $e->getMessage()
      ]);

      return false;

    } catch (\Exception $e) {
      Log::error('General error during sender credential test', [
        'email' => $this->newSenderEmail,
        'host' => $this->newSenderHost,
        'port' => $this->newSenderPort,
        'error' => $e->getMessage()
      ]);

      return false;
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
      'selectedSenderId' => $this->selectedSenderId,
      'showAddSenderForm' => $this->showAddSenderForm,
    ]);
  }
}
