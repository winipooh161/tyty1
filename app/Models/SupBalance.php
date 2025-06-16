<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SupBalance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_spent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_spent' => 'decimal:2',
    ];

    /**
     * Получить пользователя, которому принадлежит баланс.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить транзакции пользователя.
     */
    public function transactions()
    {
        return $this->hasMany(SupTransaction::class, 'user_id', 'user_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Начислить SUP пользователю.
     */
    public function addSup($amount, $description = null, $type = 'earned')
    {
        // Логируем начало операции
        Log::info('Начало операции addSup', [
            'balance_id' => $this->id,
            'user_id' => $this->user_id,
            'amount' => $amount,
            'current_balance' => $this->balance,
            'type' => $type
        ]);
        
        // Начисляем сумму на баланс
        $this->balance += $amount;
        $this->total_earned += $amount;
        
        // Сохраняем изменения
        $saved = $this->save();
        
        if (!$saved) {
            Log::error('Ошибка сохранения баланса', [
                'balance_id' => $this->id,
                'user_id' => $this->user_id
            ]);
            throw new \Exception('Ошибка при обновлении баланса');
        }

        // Создаем запись о транзакции
        $transaction = new SupTransaction([
            'sup_balance_id' => $this->id,
            'user_id' => $this->user_id,
            'amount' => $amount,
            'description' => $description ?? "Начисление SUP ({$type})",
            'type' => $type,
            'balance_after' => $this->balance
        ]);
        
        // Сохраняем транзакцию
        $transaction->save();
        
        // Логируем результат операции
        Log::info('Успешное завершение операции addSup', [
            'balance_id' => $this->id,
            'user_id' => $this->user_id,
            'new_balance' => $this->balance,
            'transaction_id' => $transaction->id
        ]);

        return $transaction;
    }

    /**
     * Списать SUP у пользователя.
     */
    public function subtractSup($amount, $description = null, $type = 'spent')
    {
        // Проверяем достаточность средств
        if (!$this->hasSufficientBalance($amount)) {
            throw new \Exception('Недостаточно средств для выполнения операции');
        }

        // Списываем сумму с баланса
        $this->balance -= $amount;
        $this->total_spent += $amount;
        $this->save();

        // Создаем запись о транзакции
        $transaction = new SupTransaction([
            'sup_balance_id' => $this->id,
            'user_id' => $this->user_id,
            'amount' => -$amount, // Отрицательное значение для списания
            'description' => $description ?? "Списание SUP ({$type})",
            'type' => $type,
            'balance_after' => $this->balance
        ]);
        
        $transaction->save();

        return $transaction;
    }

    /**
     * Проверить достаточность средств.
     */
    public function hasSufficientBalance($amount)
    {
        return $this->balance >= $amount;
    }

    /**
     * Получить отформатированный баланс.
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 0);
    }

    /**
     * Получить отформатированную сумму заработанного.
     */
    public function getFormattedTotalEarnedAttribute()
    {
        return number_format($this->total_earned, 0);
    }

    /**
     * Получить отформатированную сумму потраченного.
     */
    public function getFormattedTotalSpentAttribute()
    {
        return number_format($this->total_spent, 0);
    }

    /**
     * Получить последние транзакции.
     */
    public function getRecentTransactions($limit = 10)
    {
        return $this->transactions()->limit($limit)->get();
    }

    /**
     * Получить сумму заработанного за период.
     */
    public function getEarnedInPeriod($days = 30)
    {
        return $this->transactions()
                    ->whereIn('type', ['earned', 'bonus', 'transfer_in'])
                    ->where('created_at', '>=', now()->subDays($days))
                    ->sum('amount');
    }

    /**
     * Получить сумму потраченного за период.
     */
    public function getSpentInPeriod($days = 30)
    {
        return $this->transactions()
                    ->whereIn('type', ['spent', 'admin_deduction', 'transfer_out'])
                    ->where('created_at', '>=', now()->subDays($days))
                    ->sum('amount');
    }
}
