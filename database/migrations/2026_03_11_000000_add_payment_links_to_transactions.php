<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_number')->nullable()->after('type');
            $table->json('sale_ids')->nullable()->after('payment_number');
        });

        Schema::table('supplier_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_number')->nullable()->after('type');
            $table->json('purchase_ids')->nullable()->after('payment_number');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_number', 'sale_ids']);
        });

        Schema::table('supplier_transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_number', 'purchase_ids']);
        });
    }
};
