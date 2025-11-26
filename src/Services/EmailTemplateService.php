<?php

namespace Mrclln\MassMailer\Services;

/**
 * Service class for handling email template processing in mass mailer
 */
class EmailTemplateService
{
    /**
     * Insert a variable into the body content
     */
    public function insertVariable(string $variable, ?string $body): string
    {
        if ($variable && $body !== null) {
            $body .= " @{{ $variable }} ";
        }
        return $body ?? '';
    }

    /**
     * Preview email content with variables replaced for a specific recipient
     */
    public function previewMails(array $recipients, string $subject, string $body, array $variables): array
    {
        if (empty($recipients) || !$subject || !$body) {
            return [
                'previewEmail' => '',
                'previewContent' => '',
                'success' => false
            ];
        }

        $recipient = $recipients[0];
        $previewEmail = $recipients[0]['email'] ?? '';
        $content = $body;
        $subj = $subject;

        foreach ($variables as $variable) {
            $value = $recipient[$variable] ?? '';
            $content = str_replace(" @{{ $variable }} ", $value, $content);
            $content = str_replace("{{ $variable }}", $value, $content);
            $subj = str_replace(" @{{ $variable }} ", $value, $subj);
            $subj = str_replace("{{ $variable }}", $value, $subj);
            $previewEmail = str_replace(" @{{ $variable }} ", $value, $previewEmail);
            $previewEmail = str_replace("{{ $variable }}", $value, $previewEmail);
        }

        return [
            'previewEmail' => $previewEmail,
            'previewContent' => $content,
            'previewSubject' => $subj,
            'success' => true
        ];
    }

    /**
     * Process email template with variables for multiple recipients
     */
    public function processEmailTemplates(array $recipients, string $subject, string $body, array $variables): array
    {
        $processedRecipients = [];

        foreach ($recipients as $recipient) {
            $processedRecipient = $recipient;
            $processedSubject = $subject;
            $processedBody = $body;

            // Replace variables in subject and body
            foreach ($variables as $variable) {
                $value = $recipient[$variable] ?? '';
                $processedSubject = str_replace(" @{{ $variable }} ", $value, $processedSubject);
                $processedSubject = str_replace("{{ $variable }}", $value, $processedSubject);
                $processedBody = str_replace(" @{{ $variable }} ", $value, $processedBody);
                $processedBody = str_replace("{{ $variable }}", $value, $processedBody);
            }

            $processedRecipient['processed_subject'] = $processedSubject;
            $processedRecipient['processed_body'] = $processedBody;

            $processedRecipients[] = $processedRecipient;
        }

        return $processedRecipients;
    }

    /**
     * Validate template variables
     */
    public function validateTemplateVariables(array $variables, array $recipients): array
    {
        $missingVariables = [];
        $unusedVariables = [];

        // Check if all variables have corresponding values in recipients
        foreach ($variables as $variable) {
            $hasValues = false;
            foreach ($recipients as $recipient) {
                if (!empty($recipient[$variable])) {
                    $hasValues = true;
                    break;
                }
            }
            if (!$hasValues) {
                $missingVariables[] = $variable;
            }
        }

        // Check for unused variables (variables that exist but have no values in any recipient)
        foreach ($variables as $variable) {
            $hasAnyValue = false;
            foreach ($recipients as $recipient) {
                if (!empty($recipient[$variable])) {
                    $hasAnyValue = true;
                    break;
                }
            }
            if (!$hasAnyValue) {
                $unusedVariables[] = $variable;
            }
        }

        return [
            'missing_variables' => $missingVariables,
            'unused_variables' => $unusedVariables,
            'is_valid' => empty($missingVariables)
        ];
    }

    /**
     * Clean up template content
     */
    public function cleanTemplateContent(string $content): string
    {
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        // Trim whitespace from start and end
        $content = trim($content);

        return $content;
    }

    /**
     * Extract variables from template content
     */
    public function extractVariablesFromTemplate(string $template): array
    {
        preg_match_all('/@\s*\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $template, $matches1);
        preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $template, $matches2);
        return array_unique(array_merge($matches1[1], $matches2[1]));
    }
}
