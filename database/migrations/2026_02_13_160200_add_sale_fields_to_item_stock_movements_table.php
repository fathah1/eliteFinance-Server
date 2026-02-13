<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_stock_movements', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('item_id')->constrained('sales')->nullOnDelete();
            $table->unsignedInteger('sale_bill_number')->nullable()->after('sale_id');
        });
    }

    public function down(): void
    {
        Schema::table('item_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
            $table->dropColumn('sale_bill_number');
        });
    }
};
