<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LockoutNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $details;

    /**
     * Create a new message instance.
     *
     * @param array $details ロックアウト詳細情報
     */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.lockout.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.lockout-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
