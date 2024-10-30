<?php

namespace App\Mail;

use App\Models\HouseholdInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HouseholdInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public HouseholdInvite $invite;

    public function __construct(HouseholdInvite $invite)
    {
        $this->invite = $invite;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Household Invite',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.household-invite',
        );
    }
}
