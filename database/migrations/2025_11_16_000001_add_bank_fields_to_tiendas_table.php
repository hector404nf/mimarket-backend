<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            if (!Schema::hasColumn('tiendas', 'banco_nombre')) {
                $table->string('banco_nombre')->nullable()->after('sitio_web');
            }
            if (!Schema::hasColumn('tiendas', 'banco_cuenta')) {
                $table->string('banco_cuenta')->nullable()->after('banco_nombre');
            }
            if (!Schema::hasColumn('tiendas', 'banco_titular')) {
                $table->string('banco_titular')->nullable()->after('banco_cuenta');
            }
            if (!Schema::hasColumn('tiendas', 'banco_tipo')) {
                $table->string('banco_tipo')->nullable()->after('banco_titular');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            if (Schema::hasColumn('tiendas', 'banco_tipo')) {
                $table->dropColumn('banco_tipo');
            }
            if (Schema::hasColumn('tiendas', 'banco_titular')) {
                $table->dropColumn('banco_titular');
            }
            if (Schema::hasColumn('tiendas', 'banco_cuenta')) {
                $table->dropColumn('banco_cuenta');
            }
            if (Schema::hasColumn('tiendas', 'banco_nombre')) {
                $table->dropColumn('banco_nombre');
            }
        });
    }
};