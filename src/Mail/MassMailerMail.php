<?php

namespace Mrclln\MassMailer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MassMailerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $body;
    public $attachments;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $body, array $attachments = [])
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'mass-mailer::emails.mass-mail',
            with: [
                'body' => $this->body,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->attachments as $attachment) {
            if (isset($attachment['path']) && file_exists($attachment['path'])) {
                $attachments[] = [
                    'path' => $attachment['path'],
                    'name' => $attachment['name'] ?? null,
                    'mime' => $attachment['mime'] ?? null,
                ];
            }
        }

        return $attachments;
    }
}
