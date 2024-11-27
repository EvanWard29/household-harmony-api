<?php

use App\Models\Task;
use App\Models\User;
use App\Models\UserReminder;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_reminders', function (Blueprint $table) {
            $table->id();
            $table->tinyText('name');
            $table->unsignedInteger('length');
            $table->boolean('enabled');

            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            $table->timestamps();
        });

        // Create default 2 & 24 hour reminders
        User::chunk(1000, function (Collection $users) {
            $users->each(function (User $user) {
                app(UserService::class, ['user' => $user])->createDefaultReminders();
            });
        });

        Schema::create('task_reminders', function (Blueprint $table) {
            $table->id();
            $table->dateTime('time');

            $table->foreignIdFor(Task::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(UserReminder::class)->constrained()->cascadeOnDelete();

            $table->timestamp('sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reminders');
        Schema::dropIfExists('task_reminders');
    }
};
