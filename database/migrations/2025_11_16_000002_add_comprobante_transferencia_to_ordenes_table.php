<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes', 'comprobante_transferencia_url')) {
                $table->string('comprobante_transferencia_url')->nullable()->after('estado_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes', 'comprobante_transferencia_url')) {
                $table->dropColumn('comprobante_transferencia_url');
            }
        });
    }
};