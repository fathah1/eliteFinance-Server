<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('business_id');
            $table->string('type')->default('product');
            $table->string('name');
            $table->string('unit')->default('PCS');
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->boolean('tax_included')->default(true);
            $table->integer('opening_stock')->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('low_stock_alert')->default(0);
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
