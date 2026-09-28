<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_campaign_id')->constrained('order_campaigns')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->date('order_date')->nullable();

            $table->string('customer_name_raw');
            $table->string('customer_note_raw')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable()->comment('Khách đã khớp / đã chọn');
            $table->string('customer_match')->default('none')->comment('exact | high | low | none');
            $table->json('customer_suggestions')->nullable();

            $table->decimal('deposit_amount', 12, 0)->default(0);
            $table->decimal('discount_amount', 12, 0)->default(0);
            $table->text('note')->nullable();
            $table->boolean('is_marked_done')->default(false);
            $table->json('warnings')->nullable();
            $table->text('raw_line')->nullable();

            $table->string('status')->default('pending')->comment('pending | created | skipped');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['order_campaign_id', 'status']);
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_orders');
    }
};
