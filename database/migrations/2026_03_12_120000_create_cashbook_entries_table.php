<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbook_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('direction', 8); // in|out
            $table->decimal('amount', 12, 2);
            $table->string('payment_mode', 16)->default('cash'); // cash|card
            $table->text('note')->nullable();
            $table->date('date');
            $table->string('photo_path')->nullable();
            $table->string('del_status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_entries');
    }
};
