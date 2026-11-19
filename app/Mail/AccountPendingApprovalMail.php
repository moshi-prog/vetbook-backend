<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'VetBook — Email Verified, Awaiting Clinic Approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account_pending_approval',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
