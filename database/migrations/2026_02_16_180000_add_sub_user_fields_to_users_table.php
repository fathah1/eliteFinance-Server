<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('account_owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->boolean('is_super_user')->default(true)->after('settings');
            $table->string('pin_code')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_owner_id');
            $table->dropColumn(['is_super_user', 'pin_code']);
        });
    }
};
