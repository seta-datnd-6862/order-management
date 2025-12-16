<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_import_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_import_id');
            $table->unsignedBigInteger('product_id');
            $table->string('size');
            $table->integer('quantity');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_import_items');
    }
};