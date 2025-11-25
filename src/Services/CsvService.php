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
        Log::info('CSV file processing started', [
            'file_path' => $path,
            'file_exists' => file_exists($path),
            'file_size' => file_exists($path) ? filesize($path) : 0
        ]);

        $lines = file($path);

        if (empty($lines)) {
            Log::error('CSV file is empty', ['path' => $path]);
            return ['variables' => $defaultVariables, 'recipients' => [], 'success' => false];
        }

        // Debug: Log raw file content
        Log::info('Raw CSV file content', ['total_lines' => count($lines), 'first_few_lines' => array_slice($lines, 0, min(3, count($lines)))]);

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

        Log::info('Headers processed', [
            'original_headers' => $headers,
            'clean_headers' => $cleanHeaders
        ]);

        // Ensure email is in headers
        if (!in_array('email', $cleanHeaders)) {
            array_unshift($cleanHeaders, 'email');
            Log::info('Email column was missing, added to beginning');
        }

        // Check for attachments and CC columns
        $hasAttachmentsColumn = in_array('attachments', $cleanHeaders);
        $hasCcColumn = in_array('cc', $cleanHeaders);

        $variables = $cleanHeaders;
        $parsedRecipients = [];

        Log::info('Starting data row processing', [
            'total_data_lines' => count($lines) - 1,
            'has_attachments_column' => $hasAttachmentsColumn,
            'has_cc_column' => $hasCcColumn
        ]);

        // Process each line after header
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            // Skip empty lines
            if (empty($line)) {
                Log::debug('Skipping empty line', ['line_number' => $i]);
                continue;
            }

            $row = str_getcsv($line);

            Log::debug('Processing data row', [
                'line_number' => $i,
                'line_content' => $line,
                'parsed_row' => $row,
                'expected_columns' => count($cleanHeaders),
                'actual_columns' => count($row)
            ]);

            // Create new recipient for each row with all headers as keys
            $recipient = array_fill_keys($cleanHeaders, '');

            // Initialize attachment and CC processing variables
            $currentAttachmentPaths = [];
            $currentCcEmails = [];

            // Map CSV data to recipient fields with better error handling
            $emailFound = false;
            foreach ($cleanHeaders as $index => $fieldName) {
                $value = isset($row[$index]) ? trim($row[$index]) : '';

                Log::debug('Processing field', [
                    'field_name' => $fieldName,
                    'field_index' => $index,
                    'field_value' => $value,
                    'row_length' => count($row)
                ]);

                // Store the value in the recipient
                $recipient[$fieldName] = $value;

                // Track if we found an email
                if ($fieldName === 'email' && !empty($value)) {
                    $emailFound = true;
                }

                // Process attachments column
                if ($fieldName === 'attachments' && !empty($value)) {
                    try {
                        // Split comma-separated file paths
                        $filePaths = array_map('trim', explode(',', $value));
                        $currentAttachmentPaths = $this->attachmentService->processAttachmentPaths($filePaths, $i - 1);
                        Log::info('Processed attachment paths', [
                            'recipient_index' => $i - 1,
                            'original_value' => $value,
                            'file_paths' => $filePaths,
                            'processed_attachments' => $currentAttachmentPaths
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Error processing attachment paths', [
                            'recipient_index' => $i - 1,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                // Process CC column
                elseif ($fieldName === 'cc' && !empty($value)) {
                    // Split comma-separated email addresses
                    $emailAddresses = array_map('trim', explode(',', $value));
                    $currentCcEmails = $this->processCcEmails($emailAddresses, $i - 1);
                    Log::info('Processed CC emails', [
                        'recipient_index' => $i - 1,
                        'original_value' => $value,
                        'email_addresses' => $emailAddresses,
                        'processed_cc' => $currentCcEmails
                    ]);
                }
            }

            // Only add if we have at least an email
            if ($emailFound) {
                // Store attachment paths and CC emails in separate properties for processing
                $recipient['_auto_attachments'] = $currentAttachmentPaths;
                $recipient['_auto_cc'] = $currentCcEmails;
                $parsedRecipients[] = $recipient;
                Log::info('Added recipient from CSV row', [
                    'recipient_index' => count($parsedRecipients) - 1,
                    'email' => $recipient['email'],
                    'recipient_data' => $recipient
                ]);
            } else {
                Log::warning('Skipping row - no email found', [
                    'line_number' => $i,
                    'line_content' => $line,
                    'recipient_keys' => array_keys($recipient),
                    'recipient_values' => array_values($recipient)
                ]);
            }
        }

        Log::info('CSV parsing complete', [
            'total_lines' => count($lines),
            'headers' => $cleanHeaders,
            'has_attachments_column' => $hasAttachmentsColumn,
            'has_cc_column' => $hasCcColumn,
            'recipient_count' => count($parsedRecipients),
            'recipients_preview' => array_slice($parsedRecipients, 0, 2) // Preview of first 2 recipients
        ]);

        return [
            'variables' => $variables,
            'recipients' => $parsedRecipients,
            'success' => true
        ];
    }
}
