<?php

use App\Enums\TaskStatusEnum;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->tinyText('title');
            $table->text('description');
            $table->enum('status', TaskStatusEnum::values());
            $table->dateTime('deadline')->nullable();

            $table->foreignIdFor(User::class, 'owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(Household::class)->constrained()->cascadeOnDelete();
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->foreignIdFor(Task::class);
            $table->foreignIdFor(User::class);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_user');
    }
};
