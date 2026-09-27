<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('admin_token_hash', 64);
            $table->string('name');
            $table->string('budget')->nullable();
            $table->date('exchange_date')->nullable();
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
