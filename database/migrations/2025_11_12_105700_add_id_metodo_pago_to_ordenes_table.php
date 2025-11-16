<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes', 'id_metodo_pago')) {
                $table->unsignedBigInteger('id_metodo_pago')->nullable()->after('estado');
                $table->foreign('id_metodo_pago')
                    ->references('id_metodo')
                    ->on('metodos_pago')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes', 'id_metodo_pago')) {
                $table->dropForeign(['id_metodo_pago']);
                $table->dropColumn('id_metodo_pago');
            }
        });
    }
};

