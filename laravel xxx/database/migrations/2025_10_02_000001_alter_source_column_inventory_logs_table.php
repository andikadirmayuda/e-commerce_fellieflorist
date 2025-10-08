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
        // Change 'source' column from enum to string
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->string('source')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change 'source' column back to enum (restore original values)
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->enum('source', ['purchase', 'sale', 'return', 'adjustment'])->change();
        });
    }
};
