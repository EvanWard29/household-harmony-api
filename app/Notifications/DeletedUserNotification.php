<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeletedUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private User $remover;

    public function __construct(User $remover)
    {
        $this->remover = $remover;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $removedUser): MailMessage
    {
        return (new MailMessage)
            ->greeting("Hello {$removedUser->first_name}!")
            ->line('You have been removed from your household and your account has been deleted.')
            ->line("If you think this was a mistake, please contact {$this->remover->first_name}.");
    }
}
