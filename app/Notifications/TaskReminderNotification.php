<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification
{
    public function __construct(private readonly Task $task) {}

    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = <<<TEXT
            This is a reminder that the deadline for your assigned task,
            {$this->task->title}, is {$this->task->deadline->diffForHumans(options: Carbon::CEIL)}.
        TEXT;

        return (new MailMessage)
            ->greeting("Hello {$notifiable->first_name}!")
            ->line($message);
    }
}
