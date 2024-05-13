<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    /**
     * Reverse the migrations.
     */
    public function up(): void
    {
        Schema::create('castigo', function (Blueprint $table) {
            $table->id(column:'idCastigo');
            $table->string(column:"castigo");
            $table->time('horaInicio')->nullable(true);
            $table->time('horaFin')->nullable(true);
            $table->string(column:"observaciones")->nullable(true);
            $table->foreignId(column:'idUnidad')->nullable()->references('idUnidad')->on('unidad');
            $table->softDeletes();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('castigo');
    }
};