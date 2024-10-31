<?php

namespace App\Notifications;

use App\Models\HouseholdInvite;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HouseholdInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private HouseholdInvite $invite;

    public function __construct(HouseholdInvite $invite)
    {
        $this->invite = $invite;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $sender = \Str::possessive($this->invite->sender->first_name);

        // TODO: Langiage localisation
        return (new MailMessage)
            ->subject('Household Invitation')
            ->greeting("Hello {$notifiable->first_name}!")
            ->line("You have been invited to $sender household.")
            ->line('Please click the button below to accept your invitation.')
            ->action('Accept Invitation', "#?token={$this->invite->token}");
    }
}
