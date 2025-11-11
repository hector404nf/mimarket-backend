<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resena_likes', function (Blueprint $table) {
            $table->id('id_like');
            $table->unsignedBigInteger('id_resena');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('id_resena')->references('id_resena')->on('resenas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['id_resena', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resena_likes');
    }
};