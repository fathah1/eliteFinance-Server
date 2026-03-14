<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'vat_amount')) {
                $table->decimal('vat_amount', 12, 2)->default(0)->after('total_amount');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'vat_amount')) {
                $table->decimal('vat_amount', 12, 2)->default(0)->after('total_amount');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'vat_amount')) {
                $table->decimal('vat_amount', 12, 2)->default(0)->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
        });
    }
};
