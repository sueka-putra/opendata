<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemporaryPasswordGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $temporaryPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Open Data Portal Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.temporary-password-generated',
            with: [
                'recipientName' => $this->recipientName,
                'temporaryPassword' => $this->temporaryPassword,
            ],
        );
    }
}
