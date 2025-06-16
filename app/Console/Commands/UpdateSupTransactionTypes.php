<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateSupTransactionTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sup:update-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновляет колонку type в таблице sup_transactions для поддержки нового типа "deposit"';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Начинаем обновление типов транзакций SUP...');

        if (!Schema::hasTable('sup_transactions')) {
            $this->error('Таблица sup_transactions не найдена!');
            return 1;
        }

        // Получаем информацию о колонке
        try {
            $columnInfo = DB::select("SHOW COLUMNS FROM sup_transactions WHERE Field = 'type'")[0];
            $this->info('Текущий тип колонки: ' . $columnInfo->Type);
            
            // Если это ENUM, добавляем новое значение
            if (strpos($columnInfo->Type, 'enum(') === 0) {
                $values = substr($columnInfo->Type, 5, -1);
                
                if (strpos($values, "'deposit'") !== false) {
                    $this->info('Значение "deposit" уже присутствует в enum. Ничего не делаем.');
                    return 0;
                }
                
                $newValues = $values . ",'deposit'";
                
                $this->info('Обновляем enum с новыми значениями: ' . $newValues);
                DB::statement("ALTER TABLE sup_transactions MODIFY COLUMN type ENUM($newValues)");
                
                $this->info('Enum успешно обновлен!');
            } else {
                // Если это не enum, расширяем VARCHAR
                $this->info('Колонка type не является enum. Расширяем VARCHAR до 50 символов...');
                Schema::table('sup_transactions', function ($table) {
                    $table->string('type', 50)->change();
                });
                $this->info('Колонка успешно расширена!');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Произошла ошибка при обновлении колонки: ' . $e->getMessage());
            return 1;
        }
    }
}
