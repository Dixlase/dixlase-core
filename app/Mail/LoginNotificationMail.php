<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginNotificationMail extends Mailable
{
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public $user,
        public $ip,
        public $ua,
        public $datetime,
        public $toSystem = false
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->toSystem
            ? __('mail.login-notification.subject_system')
            : __('mail.login-notification.subject_user', ['name' => $this->user->name ?? $this->user->account_name]);

        return new Envelope(subject: $subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.login_notification',
            with: [
                'user' => $this->user,
                'ip' => $this->ip,
                'ua' => $this->ua,
                'datetime' => $this->datetime,
                'toSystem' => $this->toSystem,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
