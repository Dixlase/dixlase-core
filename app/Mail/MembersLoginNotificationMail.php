<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembersLoginNotificationMail  extends Mailable
{
    use SerializesModels;


    /**
     * Create a new message instance.
     */
    public function __construct(
        public $member,
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
            ? '【システム通知】管理画面へのログインがありました'
            : '【ログイン通知】' . $this->member->name . 'さん、ログインがありました';

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
                'member' => $this->member,
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
