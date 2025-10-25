<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorDeviceApprovalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $deviceName;
    public string $ipAddress;
    public ?string $location;
    public string $timestamp;
    public string $approvalUrl;
    public string $denyUrl;
    public string $approvalCode;
    public string $appName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $deviceName,
        string $ipAddress,
        ?string $location,
        string $timestamp,
        string $approvalUrl,
        string $denyUrl,
        string $approvalCode,
        string $appName
    ) {
        $this->deviceName = $deviceName;
        $this->ipAddress = $ipAddress;
        $this->location = $location;
        $this->timestamp = $timestamp;
        $this->approvalUrl = $approvalUrl;
        $this->denyUrl = $denyUrl;
        $this->approvalCode = $approvalCode;
        $this->appName = $appName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.device_auth.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.members_two_factor_device_approval_request',
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
