<?php

/**
 * Test script to verify attachment auto-detection functionality
 *
 * Run this script to test the CSV parsing and attachment detection:
 * php test-attachment-detection.php
 */

// Mock the Livewire component functionality for testing
class MassMailerAttachmentTest
{
    protected $recipients = [];
    protected $variables = [];
    protected $perRecipientAttachments = [];

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
                echo "⚠️  Attachment file not found: {$filePath}\n";
                continue;
            }

            // Get file information
            $fileName = basename($filePath);
            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
            $fileSize = filesize($filePath);

            $processedAttachments[] = [
                'path' => $filePath,
                'name' => $fileName,
                'mime' => $mimeType,
                'original_path' => $filePath, // Keep original for reference
                'auto_detected' => true
            ];

            echo "✅ Processed attachment: {$fileName} ({$fileSize} bytes, {$mimeType})\n";
        }

        return $processedAttachments;
    }

    public function handleCSV($csvFilePath)
    {
        if (!file_exists($csvFilePath)) {
            echo "❌ CSV file not found: {$csvFilePath}\n";
            return;
        }

        $lines = file($csvFilePath);
        if (empty($lines)) {
            echo "❌ CSV file is empty\n";
            return;
        }

        echo "📁 Processing CSV file: {$csvFilePath}\n\n";

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

        echo "📋 Headers found: " . implode(', ', $cleanHeaders) . "\n";

        // Check for attachments column
        $hasAttachmentsColumn = in_array('attachments', $cleanHeaders);
        if ($hasAttachmentsColumn) {
            echo "🔍 Attachments column detected!\n";
            $attachmentColumnIndex = array_search('attachments', $cleanHeaders);
            echo "📎 Attachment column index: {$attachmentColumnIndex}\n\n";
        } else {
            echo "❌ No attachments column found\n\n";
            return;
        }

        $this->variables = $cleanHeaders;
        $parsedRecipients = [];

        // Process each line after header
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            $row = str_getcsv($line);
            echo "📄 Processing row {$i}: " . implode(', ', $row) . "\n";

            // Create new recipient for each row
            $recipient = array_fill_keys($cleanHeaders, '');
            $attachmentPaths = [];

            // Fill fields based on CSV columns
            foreach ($cleanHeaders as $index => $fieldName) {
                if (isset($row[$index])) {
                    $value = trim($row[$index]);

                    // Process attachments column
                    if ($fieldName === 'attachments' && !empty($value)) {
                        // Split comma-separated file paths
                        $filePaths = array_map('trim', explode(',', $value));
                        $recipient[$fieldName] = $value; // Store original value for template variables
                        echo "🔗 Found attachment paths: " . implode(', ', $filePaths) . "\n";
                        $attachmentPaths = $this->processAttachmentPaths($filePaths, $i - 1);
                    } else {
                        $recipient[$fieldName] = $value;
                    }
                }
            }

            // Only add if we have at least an email
            if (!empty($recipient['email'])) {
                // Store attachment paths in a separate property for processing
                $recipient['_auto_attachments'] = $attachmentPaths;
                $parsedRecipients[] = $recipient;
                echo "✅ Added recipient: {$recipient['email']} with " . count($attachmentPaths) . " attachments\n\n";
            } else {
                echo "❌ Skipped row {$i} - no email found\n\n";
            }
        }

        $this->recipients = $parsedRecipients;
        $this->perRecipientAttachments = array_fill(0, count($parsedRecipients), []);

        echo "📊 Summary:\n";
        echo "   Total lines processed: " . count($lines) . "\n";
        echo "   Headers: " . implode(', ', $cleanHeaders) . "\n";
        echo "   Recipients found: " . count($parsedRecipients) . "\n";

        foreach ($parsedRecipients as $index => $recipient) {
            $attachments = $recipient['_auto_attachments'] ?? [];
            echo "   📧 {$recipient['email']}: " . count($attachments) . " attachments\n";
        }

        echo "\n🎉 Test completed successfully!\n";
        return $parsedRecipients;
    }
}

// Run the test
echo "🧪 Mass Mailer Attachment Auto-Detection Test\n";
echo "=============================================\n\n";

$tester = new MassMailerAttachmentTest();
$results = $tester->handleCSV('test-attachments/test-contacts.csv');

echo "\n📋 Final Results:\n";
foreach ($results as $recipient) {
    echo "👤 {$recipient['first_name']} {$recipient['last_name']} ({$recipient['email']})\n";
    if (isset($recipient['_auto_attachments'])) {
        foreach ($recipient['_auto_attachments'] as $attachment) {
            echo "   📎 {$attachment['name']} - {$attachment['mime']}\n";
        }
    }
    echo "\n";
}
