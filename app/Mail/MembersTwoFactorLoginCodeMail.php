<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembersTwoFactorLoginCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;

    /**
     * Create a new message instance.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【MySoftware】ログイン認証コードのお知らせ',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'admin::emails.members-two-factor-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
