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
}
