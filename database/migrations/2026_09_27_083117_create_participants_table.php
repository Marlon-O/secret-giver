<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('assigned_to_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->string('claim_token_hash', 64)->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->text('wishlist')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
