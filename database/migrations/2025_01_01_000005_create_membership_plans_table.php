<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-configurable premium plans: weekly / monthly / yearly, each with its own price + discount %
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Weekly Royal", "Monthly Royal", "Yearly Royal"
            $table->enum('billing_cycle', ['weekly', 'monthly', 'yearly']);
            $table->decimal('price', 12, 2);
            $table->decimal('discount_percent', 5, 2)->default(10); // % off every order while active
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
