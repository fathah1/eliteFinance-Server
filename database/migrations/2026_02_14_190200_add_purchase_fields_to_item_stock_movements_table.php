<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_stock_movements', function (Blueprint $table) {
            $table->foreignId('purchase_id')->nullable()->after('sale_bill_number')->constrained('purchases')->nullOnDelete();
            $table->unsignedInteger('purchase_number')->nullable()->after('purchase_id');
        });
    }

    public function down(): void
    {
        Schema::table('item_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_id');
            $table->dropColumn('purchase_number');
        });
    }
};
