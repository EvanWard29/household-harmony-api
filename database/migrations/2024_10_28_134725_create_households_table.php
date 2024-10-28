<?php

use App\Models\Household;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Household::class)
                ->after('id')
                ->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Household::class);
        });

        Schema::dropIfExists('households');
    }
};
