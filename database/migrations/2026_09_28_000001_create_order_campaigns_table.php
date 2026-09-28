<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->text('source_note')->nullable()->comment('Take note gốc (nếu người dùng dán vào)');
            $table->longText('raw_json')->nullable()->comment('JSON gốc đã import');
            $table->string('status')->default('in_progress')->comment('in_progress | completed');
            $table->json('import_warnings')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_campaigns');
    }
};
