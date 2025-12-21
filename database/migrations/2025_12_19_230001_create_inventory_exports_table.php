<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_exports', function (Blueprint $table) {
            $table->id();
            $table->string('export_code')->unique();
            $table->string('reason'); // Lý do xuất kho
            $table->date('export_date');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_exports');
    }
};
