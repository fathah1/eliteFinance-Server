<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('item_id');
            $table->string('type'); // in | out
            $table->integer('quantity');
            $table->decimal('price', 12, 2)->default(0);
            $table->date('date')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'business_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_stock_movements');
    }
};
