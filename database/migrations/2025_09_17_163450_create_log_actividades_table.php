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
        Schema::create('log_actividades', function (Blueprint $table) {
            $table->id('id_log');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('accion', 100);
            $table->string('tabla_afectada', 50)->nullable();
            $table->unsignedBigInteger('id_registro_afectado')->nullable();
            $table->text('detalles')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_actividades');
    }
};