<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_templates', function (Blueprint $table) {
            // Удаляем существующее ограничение внешнего ключа
            $table->dropForeign(['folder_id']);
            
            // Добавляем правильное ограничение внешнего ключа
            $table->foreign('folder_id')
                  ->references('id')
                  ->on('template_folders')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_templates', function (Blueprint $table) {
            // При отмене миграции возвращаем оригинальное ограничение 
            $table->dropForeign(['folder_id']);
            
            // Восстанавливаем старое ограничение (хотя оно неправильное)
            $table->foreign('folder_id')
                  ->references('id')
                  ->on('template_folders1')
                  ->onDelete('set null');
        });
    }
};
