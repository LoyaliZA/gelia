<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Stripe, PayPal, Mercado Pago, Kueski
            $table->decimal('commission_percent', 5, 2); // Ej: 3.60
            $table->decimal('fixed_fee', 8, 2); // Ej: 3.00
            $table->decimal('tax_rate', 5, 2)->default(16.00); // IVA sobre comisión
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
