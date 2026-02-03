<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFaDeviceMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;
    public $user; // Member or User model
    public string $ipAddress;
    public string $userAgent;
    public string $timestamp;
    public string $approveRoute;
    public string $denyRoute;

    /**
     * Create a new message instance.
     */
    public function __construct(string $token, $user, string $ipAddress, string $userAgent, string $approveRoute = 'admin.device-auth.approve', string $denyRoute = 'admin.device-auth.deny')
    {
        $this->token = $token;
        $this->user = $user;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->timestamp = now()->format('Y-m-d H:i:s');
        $this->approveRoute = $approveRoute;
        $this->denyRoute = $denyRoute;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.two-fa.device.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.two_fa_device',
            with: [
                'token' => $this->token,
                'user' => $this->user,
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
                'timestamp' => $this->timestamp,
                'approveUrl' => route($this->approveRoute, ['token' => $this->token]),
                'denyUrl' => route($this->denyRoute, ['token' => $this->token]),
            ],
        );
    }
}
