<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('scheduled_for')->nullable()->after('note');
            $table->foreignId('promo_code_id')->nullable()->after('scheduled_for')->constrained()->nullOnDelete();
            $table->decimal('promo_discount', 12, 2)->default(0)->after('promo_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
            $table->dropColumn(['scheduled_for', 'promo_discount']);
        });
    }
};
