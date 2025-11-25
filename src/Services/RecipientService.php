<?php

namespace Mrclln\MassMailer\Services;

/**
 * Service class for managing recipients in mass mailer
 */
class RecipientService
{
    /**
     * Add a new variable to the variables list
     */
    public function addVariable(string $newVariable, array $variables): array
    {
        $v = trim(strtolower($newVariable));
        $v = preg_replace('/[^a-z0-9_]/', '', $v);
        if ($v && !in_array($v, $variables)) {
            $variables[] = $v;
        }
        return $variables;
    }

    /**
     * Delete a variable from the variables list
     */
    public function deleteVariable(array $variables, int $index): array
    {
        if (isset($variables[$index])) {
            unset($variables[$index]);
            $variables = array_values($variables);
        }
        return $variables;
    }

    /**
     * Add an empty recipient with all current variables
     */
    public function addEmptyRecipient(array $variables): array
    {
        $newRecipient = array_fill_keys($variables, '');
        return $newRecipient;
    }

    /**
     * Remove a recipient from the list
     */
    public function removeRecipient(array $recipients, int $index): array
    {
        if (isset($recipients[$index])) {
            unset($recipients[$index]);
            $recipients = array_values($recipients);
        }
        return $recipients;
    }

    /**
     * Initialize recipients with default variables
     */
    public function initializeRecipients(array $variables): array
    {
        return [
            array_fill_keys($variables, '')
        ];
    }

    /**
     * Process variable handling when useAttachmentPaths changes
     */
    public function handleAttachmentPathVariableChange(bool $useAttachmentPaths, array $variables, array $recipients): array
    {
        $updatedVariables = $variables;
        $updatedRecipients = $recipients;

        if ($useAttachmentPaths) {
            // When using attachment paths, disable same attachment for all
            // Create dynamic folder name with timestamp

            // Create the folder structure: mass_mail/{user_id}/{folder_name}

            // Check if attachments variable exists, if not add it
            if (!in_array('attachments', $updatedVariables)) {
                $updatedVariables[] = 'attachments';

                // Add attachments field to existing recipients
                foreach ($updatedRecipients as &$recipient) {
                    $recipient['attachments'] = '';
                }
            }
        } else {
            // When not using attachment paths, remove attachments variable if it was auto-added
            $attachmentsIndex = array_search('attachments', $updatedVariables);
            if ($attachmentsIndex !== false && $attachmentsIndex > 2) { // Keep if it's one of the default variables
                unset($updatedVariables[$attachmentsIndex]);
                $updatedVariables = array_values($updatedVariables);

                // Remove attachments field from existing recipients
                foreach ($updatedRecipients as &$recipient) {
                    unset($recipient['attachments']);
                }
            }
        }

        return [
            'variables' => $updatedVariables,
            'recipients' => $updatedRecipients
        ];
    }

    /**
     * Update recipients variable structure when variables change
     */
    public function updateRecipientsVariableStructure(array $variables, array $recipients): array
    {
        $updatedRecipients = [];

        foreach ($recipients as $recipient) {
            $updatedRecipient = [];
            foreach ($variables as $variable) {
                $updatedRecipient[$variable] = $recipient[$variable] ?? '';
            }
            $updatedRecipients[] = $updatedRecipient;
        }

        return $updatedRecipients;
    }
}
