<?php

use App\Models\Household;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_invites', function (Blueprint $table) {
            $table->string('token', 16)->primary();
            $table->string('email');
            $table->foreignIdFor(Household::class)->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_invites');
    }
};
