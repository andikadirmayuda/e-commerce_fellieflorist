<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('public_order_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('public_order_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->string('changed_by')->nullable(); // admin, system, midtrans
            $table->string('source')->nullable(); // manual, midtrans, etc
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('public_order_id')->references('id')->on('public_orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('public_order_payment_logs');
    }
};
