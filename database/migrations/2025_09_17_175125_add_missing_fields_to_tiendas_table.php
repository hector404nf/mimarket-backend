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
        Schema::table('tiendas', function (Blueprint $table) {
            $table->string('categoria_principal')->nullable()->after('descripcion');
            $table->string('sitio_web')->nullable()->after('email_contacto');
            $table->decimal('latitud', 10, 8)->nullable()->after('sitio_web');
            $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            $table->dropColumn(['categoria_principal', 'sitio_web', 'latitud', 'longitud']);
        });
    }
};
