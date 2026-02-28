<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['orders', 'customers', 'products', 'inventory_imports', 'inventory_exports'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });

            // Migrate existing data to user id=1
            DB::table($table)->whereNull('user_id')->update(['user_id' => 1]);
        }

        // Add foreign key constraints after data migration
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        $tables = ['orders', 'customers', 'products', 'inventory_imports', 'inventory_exports'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
