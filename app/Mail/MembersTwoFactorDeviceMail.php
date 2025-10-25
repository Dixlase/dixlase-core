<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembersTwoFactorDeviceMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;
    public Member $member;
    public string $ipAddress;
    public string $userAgent;
    public string $timestamp;

    /**
     * Create a new message instance.
     */
    public function __construct(string $token, Member $member, string $ipAddress, string $userAgent)
    {
        $this->token = $token;
        $this->member = $member;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->timestamp = now()->format('Y-m-d H:i:s');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.two_factor.device.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.members_two_factor_device',
            with: [
                'token' => $this->token,
                'member' => $this->member,
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
                'timestamp' => $this->timestamp,
                'approveUrl' => route('admin.device-auth.approve', ['token' => $this->token]),
                'denyUrl' => route('admin.device-auth.deny', ['token' => $this->token]),
            ],
        );
    }
}
