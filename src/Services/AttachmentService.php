<?php

namespace Mrclln\MassMailer\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service class for handling file attachments in mass mailer
 */
class AttachmentService
{
    /**
     * Create the folder structure for attachment files
     */
    public function createAttachmentFolder(string $folderName): bool
    {
        $userId = auth()->id() ?: 'guest';
        $basePath = storage_path('app/public/mass_mail');
        $userPath = $basePath . '/' . $userId;
        $folderPath = $userPath . '/' . $folderName;

        // Create directories if they don't exist with proper permissions
        $directories = [
            ['path' => $basePath, 'name' => 'base mass_mail directory'],
            ['path' => $userPath, 'name' => 'user directory'],
            ['path' => $folderPath, 'name' => 'folder directory']
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir['path'])) {
                $created = mkdir($dir['path'], 0755, true);
                if ($created) {
                    // Set proper permissions for Laravel storage
                    if (function_exists('chmod')) {
                        chmod($dir['path'], 0755);
                    }
                    Log::info('Created directory', [
                        'directory' => $dir['name'],
                        'path' => $dir['path'],
                        'user_id' => $userId,
                        'folder_name' => $folderName
                    ]);
                } else {
                    Log::error('Failed to create directory', [
                        'directory' => $dir['name'],
                        'path' => $dir['path'],
                        'user_id' => $userId,
                        'folder_name' => $folderName
                    ]);
                }
            } else {
                // Ensure existing directories have proper permissions
                if (function_exists('chmod')) {
                    chmod($dir['path'], 0755);
                }
            }
        }

        // Verify final directory is writable
        if (is_dir($folderPath) && is_writable($folderPath)) {
            Log::info('Attachment folder structure created and verified', [
                'user_id' => $userId,
                'folder_name' => $folderName,
                'folder_path' => $folderPath,
                'writable' => true,
                'exists' => true
            ]);
            return true;
        } else {
            Log::error('Attachment folder creation failed or directory not writable', [
                'user_id' => $userId,
                'folder_name' => $folderName,
                'folder_path' => $folderPath,
                'exists' => is_dir($folderPath),
                'writable' => is_writable($folderPath),
                'permissions' => substr(sprintf('%o', fileperms($folderPath)), -4)
            ]);
            return false;
        }
    }

    /**
     * Save uploaded attachment files to the folder structure
     */
    public function saveAttachmentFilesToFolder(array $attachmentFiles, string $folderName): array
    {
        if (!$folderName || empty($attachmentFiles)) {
            return [];
        }

        $storedFiles = [];
        $userId = auth()->id() ?: 'guest';
        $disk = config('mass-mailer.attachments.storage_disk', 'public');
        $folderPath = $userId . '/' . $folderName;

        // Ensure folder exists with proper permissions
        $this->createAttachmentFolder($folderName);

        foreach ($attachmentFiles as $file) {
            if ($file) {
                try {
                    $fileName = $file->getClientOriginalName();

                    // Sanitize filename to avoid conflicts
                    $sanitizedFileName = $this->sanitizeFileName($fileName);

                    // Use Laravel's storage system instead of raw move
                    $relativePath = 'mass_mail/' . $folderPath . '/' . $sanitizedFileName;
                    $storedPath = $file->storeAs('mass_mail/' . $folderPath, $sanitizedFileName, $disk);

                    // Get the full path for verification
                    $fullFilePath = storage_path('app/public/' . $storedPath);

                    // Verify the file was stored successfully
                    if (file_exists($fullFilePath)) {
                        Log::info('Successfully saved attachment file to folder', [
                            'original_name' => $fileName,
                            'stored_name' => $sanitizedFileName,
                            'stored_path' => $storedPath,
                            'full_path' => $fullFilePath,
                            'folder_path' => $folderPath,
                            'user_id' => $userId,
                            'disk' => $disk,
                            'file_exists' => file_exists($fullFilePath),
                            'file_size' => filesize($fullFilePath)
                        ]);

                        $storedFiles[] = [
                            'path' => $fullFilePath,
                            'name' => $sanitizedFileName,
                            'mime' => $file->getClientMimeType(),
                            'stored_path' => $storedPath
                        ];
                    } else {
                        Log::warning('File storage appeared to succeed but file not found', [
                            'original_name' => $fileName,
                            'stored_path' => $storedPath,
                            'full_path' => $fullFilePath,
                            'folder_path' => $folderPath
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save attachment file', [
                        'file_name' => $file->getClientOriginalName(),
                        'folder_path' => $folderPath,
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }

        return $storedFiles;
    }

    /**
     * Sanitize filename to avoid conflicts and security issues
     */
    public function sanitizeFileName(string $fileName): string
    {
        // Remove any path components
        $fileName = basename($fileName);

        // Replace any non-alphanumeric characters with underscore
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        // Ensure we don't have multiple consecutive underscores
        $fileName = preg_replace('/_+/', '_', $fileName);

        // Remove leading/trailing underscores
        $fileName = trim($fileName, '_');

        // If filename is empty after sanitization, generate a random one
        if (empty($fileName)) {
            $fileName = 'file_' . uniqid() . '.txt';
        }

        return $fileName;
    }

    /**
     * Process attachment file paths from CSV data.
     */
    public function processAttachmentPaths(array $filePaths, int $recipientIndex): array
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
     * Process attachment files from the uploaded folder structure.
     */
    public function processAttachmentFilesFromFolder(array $fileNames, int $recipientIndex, string $folderName): array
    {
        $processedAttachments = [];
        $userId = auth()->id() ?: 'guest';
        $folderPath = storage_path('app/public/mass_mail/' . $userId . '/' . $folderName);

        foreach ($fileNames as $fileName) {
            if (empty($fileName)) continue;

            // Clean the file name
            $fileName = trim($fileName);
            $filePath = $folderPath . '/' . $fileName;

            // Check if file exists in the upload folder
            if (!file_exists($filePath)) {
                Log::warning('Attachment file not found in upload folder', [
                    'recipient_index' => $recipientIndex,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'folder_path' => $folderPath,
                    'user_id' => $userId,
                    'exists' => false
                ]);
                continue;
            }

            // Get file information
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
                'from_folder' => true
            ];

            Log::info('Processed attachment file from folder', [
                'recipient_index' => $recipientIndex,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'file_size' => $fileSize
            ]);
        }

        return $processedAttachments;
    }

    /**
     * Try to upload an attachment file from a local path to the server.
     * This handles cases where CSV contains paths that might be accessible
     * via network drives or shared folders.
     */
    public function tryUploadAttachmentFile(string $filePath, int $recipientIndex): ?string
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
}
