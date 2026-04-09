<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (!Schema::hasColumn('businesses', 'sales_tax_enabled')) {
                $table->boolean('sales_tax_enabled')->default(false)->after('name');
            }
            if (!Schema::hasColumn('businesses', 'purchase_tax_enabled')) {
                $table->boolean('purchase_tax_enabled')->default(false)->after('sales_tax_enabled');
            }
            if (!Schema::hasColumn('businesses', 'address_note')) {
                $table->text('address_note')->nullable()->after('purchase_tax_enabled');
            }
            if (!Schema::hasColumn('businesses', 'trn_no')) {
                $table->text('trn_no')->nullable()->after('address_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'trn_no')) {
                $table->dropColumn('trn_no');
            }
            if (Schema::hasColumn('businesses', 'address_note')) {
                $table->dropColumn('address_note');
            }
            if (Schema::hasColumn('businesses', 'purchase_tax_enabled')) {
                $table->dropColumn('purchase_tax_enabled');
            }
            if (Schema::hasColumn('businesses', 'sales_tax_enabled')) {
                $table->dropColumn('sales_tax_enabled');
            }
        });
    }
};
