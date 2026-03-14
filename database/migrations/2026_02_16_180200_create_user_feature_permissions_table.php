<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_feature_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('feature', 40);
            $table->boolean('can_view')->default(false);
            $table->boolean('can_add')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'feature']);
            $table->index(['account_owner_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feature_permissions');
    }
};
