<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'activation_code')) {
                $table->string('activation_code')->nullable()->after('shop_name');
            }
            if (!Schema::hasColumn('users', 'account_type')) {
                $table->string('account_type')->default('personal')->after('activation_code');
            }
            if (!Schema::hasColumn('users', 'outlet_count')) {
                $table->unsignedInteger('outlet_count')->nullable()->after('account_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'outlet_count')) {
                $table->dropColumn('outlet_count');
            }
            if (Schema::hasColumn('users', 'account_type')) {
                $table->dropColumn('account_type');
            }
            if (Schema::hasColumn('users', 'activation_code')) {
                $table->dropColumn('activation_code');
            }
        });
    }
};
