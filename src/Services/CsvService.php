<?php

namespace Mrclln\MassMailer\Services;

use Illuminate\Support\Facades\Log;
use Mrclln\MassMailer\Services\AttachmentService;
use Mrclln\MassMailer\Services\CsvService;

/**
 * Service class for handling CSV parsing and processing in mass mailer
 */
class CsvService
{
    protected AttachmentService $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Process CC email addresses from CSV data.
     */
    public function processCcEmails(array $emailAddresses, int $recipientIndex): array
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

    /**
     * Handle CSV file processing and parsing
     */
    public function handleCSV($csvFile, array $defaultVariables = []): array
    {
        if (!$csvFile) {
            return ['variables' => $defaultVariables, 'recipients' => [], 'success' => false];
        }

        $path = $csvFile->getRealPath();
        $lines = file($path);

        if (empty($lines)) {
            return ['variables' => $defaultVariables, 'recipients' => [], 'success' => false];
        }

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

        $variables = $cleanHeaders;
        $parsedRecipients = [];

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

            // Initialize variables to prevent scope issues
            $currentAttachmentPaths = [];
            $currentCcEmails = [];

            // Fill fields based on CSV columns
            foreach ($cleanHeaders as $index => $fieldName) {
                if (isset($row[$index])) {
                    $value = trim($row[$index]);

                    // Process attachments column
                    if ($fieldName === 'attachments' && !empty($value)) {
                        // Split comma-separated file paths
                        $filePaths = array_map('trim', explode(',', $value));
                        $recipient[$fieldName] = $value; // Store original value for template variables
                        $currentAttachmentPaths = $this->attachmentService->processAttachmentPaths($filePaths, $i - 1);
                        Log::info('Processed attachment paths', [
                            'recipient_index' => $i - 1,
                            'original_value' => $value,
                            'file_paths' => $filePaths,
                            'processed_attachments' => $currentAttachmentPaths
                        ]);
                    }
                    // Process CC column
                    elseif ($fieldName === 'cc' && !empty($value)) {
                        // Split comma-separated email addresses
                        $emailAddresses = array_map('trim', explode(',', $value));
                        $recipient[$fieldName] = $value; // Store original value for template variables
                        $currentCcEmails = $this->processCcEmails($emailAddresses, $i - 1);
                        Log::info('Processed CC emails', [
                            'recipient_index' => $i - 1,
                            'original_value' => $value,
                            'email_addresses' => $emailAddresses,
                            'processed_cc' => $currentCcEmails
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
                $recipient['_auto_attachments'] = $currentAttachmentPaths;
                $recipient['_auto_cc'] = $currentCcEmails;
                $parsedRecipients[] = $recipient;
                Log::info('Added recipient from CSV row', ['recipient' => $recipient]);
            }
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

                    // Initialize variables to prevent scope issues
                    $currentAttachmentPaths = [];
                    $currentCcEmails = [];

                    foreach ($cleanHeaders as $index => $header) {
                        $value = trim($row[$index] ?? '');
                        if ($header === 'attachments' && !empty($value)) {
                            $filePaths = array_map('trim', explode(',', $value));
                            $recipient[$header] = $value; // Store original value for template variables
                            $currentAttachmentPaths = $this->attachmentService->processAttachmentPaths($filePaths, $i - 1);
                        }
                        elseif ($header === 'cc' && !empty($value)) {
                            $emailAddresses = array_map('trim', explode(',', $value));
                            $recipient[$header] = $value; // Store original value for template variables
                            $currentCcEmails = $this->processCcEmails($emailAddresses, $i - 1);
                        }
                        else {
                            $recipient[$header] = $value;
                        }
                    }
                    if (!empty($recipient['email'])) {
                        $recipient['_auto_attachments'] = $currentAttachmentPaths;
                        $recipient['_auto_cc'] = $currentCcEmails;
                        $parsedRecipients[] = $recipient;
                    }
                }
            }
        }

        Log::info('CSV parsing complete', [
            'total_lines' => count($lines),
            'headers' => $cleanHeaders,
            'has_attachments_column' => $hasAttachmentsColumn,
            'has_cc_column' => $hasCcColumn,
            'recipient_count' => count($parsedRecipients),
            'recipients' => $parsedRecipients
        ]);

        return [
            'variables' => $variables,
            'recipients' => $parsedRecipients,
            'success' => true
        ];
    }
}
