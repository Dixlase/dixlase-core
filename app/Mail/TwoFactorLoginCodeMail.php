<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorLoginCodeMail extends Mailable
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
        $subjectKey = match ($this->context) {
            'admin' => 'mail.two_factor.admin.subject',
            'user' => 'mail.two_factor.user.subject',
            default => 'mail.two_factor.default.subject',
        };

        return new Envelope(
            subject: __($subjectKey, ['app_name' => $this->appName]),
        );
    }

    public function content(): Content
    {
        $template = match ($this->context) {
            'admin' => 'emails.members_two_factor_code',
            'user' => 'emails.members_two_factor_code',
            default => 'emails.members_two_factor_code',
        };

        return new Content(
            markdown: $template,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
