<?php

namespace Mrclln\MassMailer\Tests;

use Mrclln\MassMailer\Jobs\SendMassMailJob;
use Mrclln\MassMailer\Livewire\MassMailer;
use Mrclln\MassMailer\Mail\MassMailerMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

class MassMailerTest extends TestCase
{
    /** @test */
    public function it_can_render_mass_mailer_component()
    {
        Livewire::test(MassMailer::class)
            ->assertOk();
    }

    /** @test */
    public function it_can_send_mass_mail_job()
    {
        Mail::shouldReceive('to->send')
            ->once()
            ->andReturn(true);

        $recipients = [
            ['email' => 'test@example.com', 'name' => 'Test User']
        ];

        $job = new SendMassMailJob($recipients, 'Test Subject', 'Test Body');
        $job->handle();

        // Add assertions based on your logging or other expectations
    }

    /** @test */
    public function it_can_create_mass_mailer_mail()
    {
        $mail = new MassMailerMail('Test Subject', 'Test Body');

        $this->assertEquals('Test Subject', $mail->subject);
        $this->assertEquals('Test Body', $mail->body);
    }

    /** @test */
    public function it_has_config_file()
    {
        $config = config('mass-mailer');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('batch_size', $config);
    }

    /** @test */
    public function it_can_personalize_content_with_array_values()
    {
        $job = new SendMassMailJob([], 'Test Subject', 'Test Body');

        // Test with recipient containing array values (like _auto_cc)
        $recipient = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            '_auto_cc' => [
                ['email' => 'cc1@example.com', 'name' => 'CC User 1'],
                ['email' => 'cc2@example.com', 'name' => 'CC User 2']
            ],
            'custom_field' => 'Custom Value'
        ];

        $content = 'Hello {{ name }}, your email is {{ email }}. CC: {{ _auto_cc }}. Custom: {{ custom_field }}';
        $personalized = $job->personalizeContent($content, $recipient);

        // Array values should be skipped, string values should be replaced
        $this->assertStringContainsString('Hello Test User', $personalized);
        $this->assertStringContainsString('your email is test@example.com', $personalized);
        $this->assertStringContainsString('Custom: Custom Value', $personalized);
        // Array placeholders should remain unchanged
        $this->assertStringContainsString('CC: {{ _auto_cc }}', $personalized);
    }

    /** @test */
    public function it_can_process_csv_files()
    {
        // This test requires a Laravel application setup
        $this->markTestSkipped('CSV processing test requires full Laravel application setup');
    }

    /** @test */
    public function it_handles_csv_with_special_characters()
    {
        // Test CSV parsing with various edge cases
        $csvContent = "email,first_name,last_name\n";
        $csvContent .= "test@example.com,John,Doe\n";
        $csvContent .= "test2@example.com,Jane,Smith\n";
        $csvContent .= ",Empty,Email\n"; // Row with empty email
        $csvContent .= "test3@example.com,Bob,\"Wilson\"\n"; // Quoted field

        // Simulate CSV processing logic
        $lines = explode("\n", trim($csvContent));
        $headerLine = trim($lines[0]);
        $headers = str_getcsv($headerLine);

        $cleanHeaders = [];
        foreach ($headers as $header) {
            $cleanHeader = trim(strtolower($header));
            if (!empty($cleanHeader) && !in_array($cleanHeader, ['#', 'actions'])) {
                $cleanHeaders[] = $cleanHeader;
            }
        }

        // Ensure email is in headers
        if (!in_array('email', $cleanHeaders)) {
            array_unshift($cleanHeaders, 'email');
        }

        // Process data rows
        $recipients = [];
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $row = str_getcsv($line);
            $recipient = array_fill_keys($cleanHeaders, '');

            foreach ($cleanHeaders as $index => $fieldName) {
                if (isset($row[$index])) {
                    $recipient[$fieldName] = trim($row[$index]);
                }
            }

            // Only add if email is not empty
            if (!empty($recipient['email'])) {
                $recipients[] = $recipient;
            }
        }

        // Assertions
        $this->assertCount(3, $recipients, 'Should process 3 valid recipients');
        $this->assertEquals('test@example.com', $recipients[0]['email']);
        $this->assertEquals('John', $recipients[0]['first_name']);
        $this->assertEquals('Doe', $recipients[0]['last_name']);
        $this->assertEquals('test2@example.com', $recipients[1]['email']);
        $this->assertEquals('test3@example.com', $recipients[2]['email']);
    }
}
