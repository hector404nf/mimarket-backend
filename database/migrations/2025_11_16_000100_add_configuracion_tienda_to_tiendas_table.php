<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            if (!Schema::hasColumn('tiendas', 'configuracion_tienda')) {
                $table->json('configuracion_tienda')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            if (Schema::hasColumn('tiendas', 'configuracion_tienda')) {
                $table->dropColumn('configuracion_tienda');
            }
        });
    }
};