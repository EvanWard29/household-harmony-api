<?php

use App\Models\Group;
use App\Models\Household;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->tinyText('name');
            $table->text('description')->nullable();

            $table->foreignIdFor(Household::class)->constrained()->cascadeOnDelete();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignIdFor(Group::class)
                ->nullable()
                ->after('deadline')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Group::class);
        });

        Schema::dropIfExists('groups');
    }
};
