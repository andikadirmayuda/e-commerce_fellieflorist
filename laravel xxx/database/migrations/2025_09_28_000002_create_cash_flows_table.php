<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->enum('type', ['inflow', 'outflow']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->enum('payment_method', ['cash', 'transfer', 'ewallet']);
            $table->date('transaction_date');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('cash_flow_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_flows');
    }
};
