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
        Schema::table('doctor_ratings', function (Blueprint $table) {
            $table->foreignId('laboratory_result_id')->nullable()
                ->constrained('laboratory_results')
                ->onDelete('cascade');
            
            // Agregar restricción única para asegurar que un usuario no pueda calificar el mismo resultado más de una vez
            $table->unique(['user_id', 'laboratory_result_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_ratings', function (Blueprint $table) {
            $table->dropForeign(['laboratory_result_id']);
            $table->dropColumn('laboratory_result_id');
            $table->dropUnique(['user_id', 'laboratory_result_id']);
        });
    }
};
