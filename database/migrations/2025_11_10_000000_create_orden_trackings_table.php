<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orden_trackings', function (Blueprint $table) {
            $table->bigIncrements('id_tracking');
            $table->unsignedBigInteger('id_orden');
            $table->decimal('latitud', 10, 6)->nullable();
            $table->decimal('longitud', 10, 6)->nullable();
            $table->float('precision')->nullable();
            $table->float('velocidad')->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->string('fuente', 32)->nullable(); // store_app | driver_app
            $table->boolean('tracking_activo')->default(false);
            $table->timestamps();

            $table->foreign('id_orden')->references('id_orden')->on('ordenes')->onDelete('cascade');
            $table->unique('id_orden'); // una fila por orden (snapshot actual)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_trackings');
    }
};