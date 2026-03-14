<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->foreignId('expense_catalog_item_id')->nullable()->after('item_id')->constrained('expense_catalog_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_catalog_item_id');
        });
    }
};
