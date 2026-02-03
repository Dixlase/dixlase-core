<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFaCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $context;
    public string $appName;

    /**
     * Create a new message instance.
     *
     * @param string $code 二段階認証コード
     * @param string $context コンテキスト（admin, user等）
     * @param string $appName アプリケーション名
     */
    public function __construct(string $code, string $context = 'admin', string $appName = null)
    {
        $this->code = $code;
        $this->context = $context;
        $this->appName = $appName ?? config('app.name', 'Dixlase');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.two-fa.email.subject', ['app_name' => $this->appName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.two-fa-code',
            with: [
                'code' => $this->code,
                'appName' => $this->appName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
