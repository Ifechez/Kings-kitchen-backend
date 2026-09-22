<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2); // regular price in Naira
            $table->decimal('discount_price', 12, 2)->nullable(); // price shown to premium members / promo price
            $table->string('image')->nullable(); // stored path
            $table->boolean('is_available')->default(true);
            $table->boolean('is_hot')->default(false); // "Hot on the Menu" flag, can be manual or auto
            $table->unsignedInteger('order_count')->default(0); // incremented per order, drives "most ordered"
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
