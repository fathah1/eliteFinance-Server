<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedInteger('bill_number');
            $table->date('date');
            $table->string('party_name')->nullable();
            $table->string('party_phone')->nullable();
            $table->decimal('manual_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('additional_charges_total', 12, 2)->default(0);
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->string('discount_type', 20)->default('aed');
            $table->string('discount_label')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_mode', 20)->default('unpaid');
            $table->decimal('received_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->string('payment_status', 30)->default('unpaid');
            $table->date('due_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('private_notes')->nullable();
            $table->json('note_photos')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'business_id']);
            $table->index(['business_id', 'bill_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
