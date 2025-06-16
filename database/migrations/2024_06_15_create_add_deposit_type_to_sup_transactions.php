<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddDepositTypeToSupTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Получаем информацию о текущей колонке type
        $columnInfo = DB::select("SHOW COLUMNS FROM sup_transactions WHERE Field = 'type'")[0];
        
        // Получаем текущий тип колонки (например "enum('bonus','transfer_in','transfer_out','admin_deduction')")
        $currentType = $columnInfo->Type;
        
        // Проверяем, что это действительно enum
        if (strpos($currentType, 'enum(') === 0) {
            // Извлекаем значения из enum (удаляем 'enum(' в начале и ')' в конце)
            $values = substr($currentType, 5, -1);
            
            // Если значение 'deposit' уже есть, не делаем ничего
            if (strpos($values, "'deposit'") !== false) {
                return;
            }
            
            // Добавляем новое значение 'deposit' к списку
            $newValues = $values . ",'deposit'";
            
            // Применяем изменение
            DB::statement("ALTER TABLE sup_transactions MODIFY COLUMN type ENUM($newValues)");
        } else {
            // Если это не enum, просто расширяем размер колонки VARCHAR
            Schema::table('sup_transactions', function (Blueprint $table) {
                $table->string('type', 50)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Получаем информацию о текущей колонке type
        $columnInfo = DB::select("SHOW COLUMNS FROM sup_transactions WHERE Field = 'type'")[0];
        $currentType = $columnInfo->Type;
        
        // Проверяем, что это действительно enum
        if (strpos($currentType, 'enum(') === 0) {
            // Извлекаем значения из enum
            $values = substr($currentType, 5, -1);
            
            // Удаляем 'deposit' из списка значений
            $newValues = str_replace(",'deposit'", "", $values);
            
            // Применяем изменение
            DB::statement("ALTER TABLE sup_transactions MODIFY COLUMN type ENUM($newValues)");
        }
    }
}
