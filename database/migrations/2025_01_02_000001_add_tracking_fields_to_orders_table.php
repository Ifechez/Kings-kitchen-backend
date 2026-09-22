<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Timestamps for each stage, so the customer sees a real Glovo-style
            // timeline ("Confirmed at 10:32am", "Out for delivery at 10:50am"...).
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->timestamp('preparing_at')->nullable()->after('confirmed_at');
            $table->timestamp('out_for_delivery_at')->nullable()->after('preparing_at');
            $table->timestamp('delivered_at')->nullable()->after('out_for_delivery_at');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');

            // Optional destination pin, captured from the customer's browser at checkout.
            $table->decimal('delivery_lat', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('delivery_lng', 10, 7)->nullable()->after('delivery_lat');

            // Live courier position, updated by whoever is delivering (via the admin panel).
            $table->decimal('courier_lat', 10, 7)->nullable()->after('delivery_lng');
            $table->decimal('courier_lng', 10, 7)->nullable()->after('courier_lat');
            $table->timestamp('courier_updated_at')->nullable()->after('courier_lng');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'confirmed_at', 'preparing_at', 'out_for_delivery_at', 'delivered_at', 'cancelled_at',
                'delivery_lat', 'delivery_lng', 'courier_lat', 'courier_lng', 'courier_updated_at',
            ]);
        });
    }
};
