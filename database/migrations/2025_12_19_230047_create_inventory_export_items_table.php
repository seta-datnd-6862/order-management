<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_export_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_export_id');
            $table->unsignedBigInteger('product_id');
            $table->string('size');
            $table->integer('quantity');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_export_items');
    }
};
