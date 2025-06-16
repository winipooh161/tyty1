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
        // Проверяем, существует ли таблица template_folders
        if (!Schema::hasTable('template_folders')) {
            Schema::create('template_folders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('name');
                $table->string('color')->nullable()->default('#6c757d');
                $table->integer('display_order')->default(0);
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Проверяем, существует ли таблица user_templates и колонка folder_id
        if (Schema::hasTable('user_templates') && !Schema::hasColumn('user_templates', 'folder_id')) {
            Schema::table('user_templates', function (Blueprint $table) {
                $table->unsignedBigInteger('folder_id')->nullable()->after('template_id');
                $table->foreign('folder_id')->references('id')->on('template_folders')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Проверяем, существует ли таблица user_templates и колонка folder_id
        if (Schema::hasTable('user_templates') && Schema::hasColumn('user_templates', 'folder_id')) {
            Schema::table('user_templates', function (Blueprint $table) {
                // Проверяем существование внешнего ключа перед удалением
                $foreignKeys = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableForeignKeys('user_templates');
                
                $foreignKeyExists = collect($foreignKeys)->contains(function ($key) {
                    return in_array('folder_id', $key->getLocalColumns());
                });
                
                if ($foreignKeyExists) {
                    $table->dropForeign(['folder_id']);
                }
                
                $table->dropColumn('folder_id');
            });
        }
        
        Schema::dropIfExists('template_folders');
    }
};
