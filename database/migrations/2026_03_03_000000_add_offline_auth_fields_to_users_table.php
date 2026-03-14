<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'offline_auth_salt')) {
                $table->string('offline_auth_salt', 64)->nullable()->after('pin_code_lookup');
            }
            if (!Schema::hasColumn('users', 'offline_auth_version')) {
                $table->unsignedSmallInteger('offline_auth_version')->default(1)->after('offline_auth_salt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'offline_auth_version')) {
                $table->dropColumn('offline_auth_version');
            }
            if (Schema::hasColumn('users', 'offline_auth_salt')) {
                $table->dropColumn('offline_auth_salt');
            }
        });
    }
};

