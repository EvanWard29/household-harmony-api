<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_reminders', function (Blueprint $table) {
            $table->id();
            $table->dateTime('time');

            $table->foreignIdFor(Task::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'recipient_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminders');
    }
};
