<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_business_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'business_id']);
            $table->index(['account_owner_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_business_accesses');
    }
};
