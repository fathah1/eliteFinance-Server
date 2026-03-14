<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->json('allocations')->nullable()->after('sale_ids');
        });

        Schema::table('supplier_transactions', function (Blueprint $table) {
            $table->json('allocations')->nullable()->after('purchase_ids');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('allocations');
        });

        Schema::table('supplier_transactions', function (Blueprint $table) {
            $table->dropColumn('allocations');
        });
    }
};
