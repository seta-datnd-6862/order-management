<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_order_id')->constrained('campaign_orders')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);

            $table->string('product_name_raw');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_match')->default('none')->comment('exact | high | low | none');
            $table->json('product_suggestions')->nullable();

            $table->string('size_raw')->nullable()->comment('Size nguyên văn trong note');
            $table->string('size')->nullable()->comment('Size đã chuẩn hoá theo whitelist');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 12, 0)->nullable();
            $table->text('note')->nullable();
            $table->text('raw_text')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_order_items');
    }
};
