<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('giver_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('participants')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['giver_id', 'receiver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exclusions');
    }
};
