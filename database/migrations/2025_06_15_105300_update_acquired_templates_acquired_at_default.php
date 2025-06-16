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
        Schema::table('acquired_templates', function (Blueprint $table) {
            // Изменяем поле acquired_at, чтобы оно имело значение по умолчанию
            $table->timestamp('acquired_at')->default(DB::raw('CURRENT_TIMESTAMP'))->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acquired_templates', function (Blueprint $table) {
            // Возвращаем поле в исходное состояние (без значения по умолчанию)
            $table->timestamp('acquired_at')->nullable(false)->change();
        });
    }
};
