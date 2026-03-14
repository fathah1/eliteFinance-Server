<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_mode', 20)->nullable()->after('payment_number');
        });

        Schema::table('supplier_transactions', function (Blueprint $table) {
            $table->string('payment_mode', 20)->nullable()->after('payment_number');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('payment_mode');
        });

        Schema::table('supplier_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_mode');
        });
    }
};
