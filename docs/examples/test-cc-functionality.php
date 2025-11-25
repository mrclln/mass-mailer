<?php

/**
 * Test script to verify CC email auto-detection functionality
 *
 * Run this script to test the CSV parsing and CC email detection:
 * php test-cc-functionality.php
 */

// Mock the Livewire component functionality for testing
class MassMailerCcTest
{
    protected $recipients = [];
    protected $variables = [];
    protected $perRecipientAttachments = [];

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
                echo "⚠️  Invalid CC email address: {$email}\n";
                continue;
            }

            $processedCc[] = [
                'email' => $email,
                'auto_detected' => true
            ];

            echo "✅ Processed CC email: {$email}\n";
        }

        return $processedCc;
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

        // Check for attachments and CC columns
        $hasAttachmentsColumn = in_array('attachments', $cleanHeaders);
        $hasCcColumn = in_array('cc', $cleanHeaders);

        if ($hasAttachmentsColumn) {
            echo "🔍 Attachments column detected!\n";
        } else {
            echo "❌ No attachments column found\n";
        }

        if ($hasCcColumn) {
            echo "📧 CC column detected!\n";
        } else {
            echo "❌ No CC column found\n";
        }

        if (!$hasAttachmentsColumn && !$hasCcColumn) {
            echo "❌ No special columns detected\n\n";
            return;
        }

        echo "\n";

        $this->variables = $cleanHeaders;
        $parsedRecipients = [];
        $currentRecipient = null;

        // Process each line after header
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            $row = str_getcsv($line);
            echo "📄 Processing row {$i}: " . implode(' | ', $row) . "\n";

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
                        echo "🔗 Found attachment paths: " . implode(', ', $filePaths) . "\n";
                        // Simulate attachment processing
                        foreach ($filePaths as $filePath) {
                            if (file_exists(trim($filePath))) {
                                echo "✅ File exists: " . basename($filePath) . "\n";
                            } else {
                                echo "⚠️  File not found: {$filePath}\n";
                            }
                        }
                    }
                    // Process CC column
                    elseif ($fieldName === 'cc' && !empty($value)) {
                        // Split comma-separated email addresses
                        $emailAddresses = array_map('trim', explode(',', $value));
                        $recipient[$fieldName] = $value; // Store original value for template variables
                        echo "📧 Found CC emails: " . implode(', ', $emailAddresses) . "\n";
                        $ccEmails = $this->processCcEmails($emailAddresses, $i - 1);
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
                echo "✅ Added recipient: {$recipient['email']} with " . count($ccEmails) . " CC emails\n\n";
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
            $ccEmails = $recipient['_auto_cc'] ?? [];
            echo "   📧 {$recipient['email']}: " . count($attachments) . " attachments, " . count($ccEmails) . " CC emails\n";
        }

        echo "\n🎉 CC Test completed successfully!\n";
        return $parsedRecipients;
    }
}

// Run the test
echo "🧪 Mass Mailer CC Email Auto-Detection Test\n";
echo "============================================\n\n";

$tester = new MassMailerCcTest();
$results = $tester->handleCSV('test-attachments/test-contacts-with-cc.csv');

echo "\n📋 Final Results:\n";
foreach ($results as $recipient) {
    echo "👤 {$recipient['first_name']} {$recipient['last_name']} ({$recipient['email']})\n";

    if (isset($recipient['_auto_cc'])) {
        echo "   📧 CC Emails:\n";
        foreach ($recipient['_auto_cc'] as $cc) {
            echo "      - {$cc['email']}\n";
        }
    }

    if (isset($recipient['attachments']) && !empty($recipient['attachments'])) {
        echo "   📎 Attachments: {$recipient['attachments']}\n";
    }

    echo "\n";
}
