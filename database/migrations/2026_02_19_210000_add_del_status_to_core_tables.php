<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = [
        'businesses',
        'customers',
        'suppliers',
        'transactions',
        'supplier_transactions',
        'items',
        'sales',
        'sale_items',
        'purchases',
        'purchase_items',
        'expenses',
        'expense_items',
        'expense_catalog_items',
        'expense_categories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'del_status')) {
                    $table->string('del_status', 20)->default('live')->index();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'del_status')) {
                    $table->dropColumn('del_status');
                }
            });
        }
    }
};
