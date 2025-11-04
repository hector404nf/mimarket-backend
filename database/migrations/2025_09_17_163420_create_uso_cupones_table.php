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
        Schema::create('uso_cupones', function (Blueprint $table) {
            $table->id('id_uso');
            $table->unsignedBigInteger('id_cupon');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('id_orden');
            $table->decimal('descuento_aplicado', 10, 2);
            $table->timestamps();
            
            $table->foreign('id_cupon')->references('id_cupon')->on('cupones')->onDelete('cascade');
            $table->foreign('id_orden')->references('id_orden')->on('ordenes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uso_cupones');
    }
};