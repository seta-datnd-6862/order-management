<?php
// database/migrations/xxxx_create_viettel_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('viettel_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id');
            
            // Mã vận đơn Viettel Post
            $table->string('tracking_number')->unique();
            
            // Thông tin cơ bản
            $table->string('service_code')->default('VCN'); // VCN, PHS, VCBO
            $table->string('status')->default('created'); // created, shipping, delivered, cancelled
            
            // Thông tin người nhận (lưu lại để tracking)
            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->text('receiver_address');
            
            // Thông tin vận chuyển
            $table->integer('product_weight')->nullable(); // gram
            $table->decimal('money_collection', 15, 2)->default(0); // Tiền thu hộ
            $table->decimal('shipping_fee', 15, 2)->default(0); // Cước phí
            
            // Thời gian dự kiến
            $table->double('estimated_delivery_time')->nullable(); // giờ
            
            // Ghi chú
            $table->text('note')->nullable();
            
            // Lưu response từ API (để debug)
            $table->json('api_response')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('tracking_number');
            $table->index('status');
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('viettel_orders');
    }
};
