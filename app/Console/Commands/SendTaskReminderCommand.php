<?php

namespace App\Console\Commands;

use App\Models\TaskReminder;
use App\Notifications\TaskReminderNotification;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;

class SendTaskReminderCommand extends Command
{
    protected $signature = 'task:send-reminders';

    protected $description = 'Send the scheduled task reminders of the current minute.';

    public function handle(): void
    {
        $sendReminders = function () {
            // Get all task reminders within the given minute
            $reminders = TaskReminder::whereBetween('time', [now()->startOfMinute(), now()->endOfMinute()])
                ->with(['task', 'recipient'])
                ->get();

            $reminders->each(function (TaskReminder $reminder) {
                // Send a reminder to the recipient
                $reminder->recipient->notify(new TaskReminderNotification($reminder->task));
            });

            // Mark the reminders as sent
            if ($reminders->isNotEmpty()) {
                TaskReminder::whereIn('id', $reminders->pluck('id'))->update(['sent_at' => now()]);
            }

            return $reminders->count();
        };

        [$count, $duration] = Benchmark::value($sendReminders);

        // Convert the duration to seconds
        $duration = CarbonInterval::milliseconds($duration)->totalSeconds;

        $this->info("{$count} reminders sent in {$duration} seconds.");
    }
}
