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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('deposit_amount', 15, 2)->default(0)->after('total_amount')->comment('Số tiền đã cọc');
            $table->string('shipping_code')->nullable()->after('note')->comment('Mã vận chuyển');
            $table->string('shipping_image')->nullable()->after('shipping_code')->comment('Ảnh mã vận chuyển');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['deposit_amount', 'shipping_code', 'shipping_image']);
        });
    }
};
