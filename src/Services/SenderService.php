<?php

namespace Mrclln\MassMailer\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

/**
 * Service class for managing email senders in mass mailer
 */
class SenderService
{
    /**
     * Test the SMTP credentials by sending a test email to the sender's own email address
     */
    public function testSenderCredentials(string $host, int $port, string $username, string $password, string $encryption, string $email, string $name): bool
    {
        try {
            // Clear any cached mail configuration first
            app()->forgetInstance('mail.manager');
            app()->forgetInstance('mailer');

            // Configure temporary SMTP settings for testing
            $tempConfig = [
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
                'encryption' => $encryption,
                'transport' => 'smtp',
            ];

            // Set the temporary configuration
            config(['mail.mailers.smtp' => $tempConfig]);
            config(['mail.default' => 'smtp']);

            // Set the from address to match the sender
            config([
                'mail.from.address' => $email,
                'mail.from.name' => $name
            ]);

            // Create a test email
            $testSubject = 'Mass Mailer - Test Email';
            $testBody = '
                <html>
                  <body>
                    <h2>Email Configuration Test</h2>
                    <p>This is a test email to verify your SMTP configuration for the Mass Mailer application.</p>
                    <p><strong>Sender:</strong> ' . htmlspecialchars($name) . ' (' . htmlspecialchars($email) . ')</p>
                    <p><strong>SMTP Server:</strong> ' . htmlspecialchars($host) . ':' . $port . ' (' . strtoupper($encryption) . ')</p>
                    <p><strong>Timestamp:</strong> ' . now()->toDateTimeString() . '</p>
                    <p>If you received this email, your SMTP configuration is working correctly!</p>
                  </body>
                </html>
            ';

            // Try to send the test email
            Mail::html($testBody, function ($message) use ($email, $name, $testSubject) {
                $message->to($email, $name)
                        ->subject($testSubject)
                        ->from($email, $name);
            });

            Log::info('Test email sent successfully', [
                'email' => $email,
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption
            ]);

            return true;

        } catch (\Swift_TransportException $e) {
            Log::error('SMTP transport error during sender credential test', [
                'email' => $email,
                'host' => $host,
                'port' => $port,
                'error' => $e->getMessage()
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('General error during sender credential test', [
                'email' => $email,
                'host' => $host,
                'port' => $port,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get all senders (config + database based)
     */
    public function getAllSenders(): array
    {
        $senders = [];

        // Load config-based senders first
        $configSenders = config('mass-mailer.senders', []);
        foreach ($configSenders as $index => $sender) {
            // Assign a negative index for config senders to avoid conflicts with DB IDs
            $senders[] = array_merge($sender, ['id' => 'config_' . $index]);
        }

        // Append database-based senders
        $senderModel = config('mass-mailer.sender_model');
        if ($senderModel && $senderModel::count() > 0) {
            $dbSenders = $senderModel::all()->toArray();
            $senders = array_merge($senders, $dbSenders);
        }

        return $senders;
    }

    /**
     * Get selected sender credentials
     */
    public function getSelectedSenderCredentials(array $senders, string $selectedSenderId): ?array
    {
        foreach ($senders as $sender) {
            if (($sender['id'] ?? null) == $selectedSenderId) {
                // For database senders, use the full sender data
                if (str_starts_with($selectedSenderId, 'config_')) {
                    // For config senders, we need to get SMTP credentials from config
                    $configSenders = config('mass-mailer.senders', []);
                    $configIndex = (int) str_replace('config_', '', $selectedSenderId);
                    if (isset($configSenders[$configIndex])) {
                        $configSender = $configSenders[$configIndex];
                        // Use the sender's own credentials from config, not the default mail config
                        return [
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
                    return [
                        'name' => $sender['name'],
                        'email' => $sender['email'],
                        'host' => $sender['host'],
                        'port' => $sender['port'],
                        'username' => $sender['username'],
                        'password' => $sender['password'],
                        'encryption' => $sender['encryption'],
                    ];
                }
                break;
            }
        }

        return null;
    }

    /**
     * Validate sender credentials
     */
    public function validateSenderCredentials(array $credentials): array
    {
        $requiredKeys = ['host', 'port', 'username', 'password', 'encryption'];
        $missingKeys = [];
        $emptyKeys = [];

        foreach ($requiredKeys as $key) {
            if (!isset($credentials[$key])) {
                $missingKeys[] = $key;
            } elseif (empty($credentials[$key])) {
                $emptyKeys[] = $key;
            }
        }

        $isValid = empty($missingKeys) && empty($emptyKeys);

        if (!$isValid) {
            $errorMessage = 'Sender credentials are incomplete. ';
            if (!empty($missingKeys)) {
                $errorMessage .= 'Missing keys: ' . implode(', ', $missingKeys) . '. ';
            }
            if (!empty($emptyKeys)) {
                $errorMessage .= 'Empty keys: ' . implode(', ', $emptyKeys) . '. ';
            }
            $errorMessage .= 'Please check the sender configuration.';
        }

        return [
            'valid' => $isValid,
            'missing_keys' => $missingKeys,
            'empty_keys' => $emptyKeys,
            'error_message' => $isValid ? null : ($errorMessage ?? 'Unknown validation error')
        ];
    }

    /**
     * Save a new sender
     */
    public function saveNewSender(array $senderData)
    {
        try {
            $senderModel = config('mass-mailer.sender_model', \Mrclln\MassMailer\Models\MassMailerSender::class);

            $newSender = $senderModel::create([
                'name' => $senderData['name'],
                'email' => $senderData['email'],
                'host' => $senderData['host'],
                'port' => $senderData['port'],
                'username' => $senderData['username'],
                'password' => $senderData['password'],
                'encryption' => $senderData['encryption'],
                'user_id' => auth()->id(),
            ]);

            return $newSender;

        } catch (\Exception $e) {
            Log::error('Failed to save new sender', [
                'error' => $e->getMessage(),
                'email' => $senderData['email']
            ]);

            throw $e;
        }
    }
}
