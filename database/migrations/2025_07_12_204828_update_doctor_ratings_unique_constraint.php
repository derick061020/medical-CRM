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
        // Eliminar la tabla existente
        Schema::dropIfExists('doctor_ratings');

        // Crear la tabla nuevamente
        Schema::create('doctor_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('laboratory_result_id')->constrained('laboratory_results')->onDelete('cascade');
            $table->integer('rating')->min(1)->max(5);
            $table->text('comment')->nullable();
            $table->unique(['user_id', 'laboratory_result_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver a la restricción original
        Schema::table('doctor_ratings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'laboratory_result_id']);
            $table->unique(['user_id', 'doctor_id']);
        });
    }
};
