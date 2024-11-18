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
        Schema::create('invoice_aop_detail', function (Blueprint $table) {
            $table->id();
            $table->string('invoiceAop');
            $table->string('SPB');
            $table->string('customerTo');
            $table->string('materialNumber');
            $table->integer('qty');
            $table->bigInteger('price');
            $table->bigInteger('extraPlafonDiscount');
            $table->bigInteger('amount');
            $table->string('uploaded_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_aop_detail');
    }
};
