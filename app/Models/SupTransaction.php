<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sup_balance_id',
        'user_id',
        'amount',
        'description',
        'type',
        'balance_after'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Получить пользователя, которому принадлежит транзакция.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить баланс, к которому относится транзакция.
     */
    public function supBalance()
    {
        return $this->belongsTo(SupBalance::class, 'sup_balance_id');
    }

    /**
     * Получить отформатированную сумму.
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0);
    }

    /**
     * Получить иконку для типа транзакции.
     */
    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'earned', 'bonus' => 'plus-circle',
            'spent', 'admin_deduction' => 'minus-circle',
            'transfer_in' => 'arrow-down-circle',
            'transfer_out' => 'arrow-up-circle',
            default => 'circle'
        };
    }

    /**
     * Получить цвет для типа транзакции.
     */
    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'earned', 'bonus', 'transfer_in' => 'success',
            'spent', 'admin_deduction', 'transfer_out' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Получить текст для типа транзакции.
     */
    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'earned' => 'Заработано',
            'spent' => 'Потрачено',
            'bonus' => 'Бонус',
            'transfer_in' => 'Получено',
            'transfer_out' => 'Отправлено',
            'admin_deduction' => 'Списание админом',
            default => 'Неизвестно'
        };
    }

    /**
     * Скоупы для фильтрации транзакций.
     */
    public function scopeEarned($query)
    {
        return $query->whereIn('type', ['earned', 'bonus', 'transfer_in']);
    }

    public function scopeSpent($query)
    {
        return $query->whereIn('type', ['spent', 'admin_deduction', 'transfer_out']);
    }

    public function scopeTransfers($query)
    {
        return $query->whereIn('type', ['transfer_in', 'transfer_out']);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Допустимые типы транзакций
     */
    const TYPE_BONUS = 'bonus';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_ADMIN_DEDUCTION = 'admin_deduction';
    const TYPE_DEPOSIT = 'deposit'; // Новый тип для пополнения баланса

    /**
     * Массив всех допустимых типов транзакций
     */
    public static $allowedTypes = [
        self::TYPE_BONUS,
        self::TYPE_TRANSFER_IN,
        self::TYPE_TRANSFER_OUT,
        self::TYPE_ADMIN_DEDUCTION,
        self::TYPE_DEPOSIT,
    ];

    /**
     * Проверка допустимости типа транзакции
     *
     * @param string $type
     * @return bool
     */
    public static function isValidType($type)
    {
        return in_array($type, self::$allowedTypes);
    }
}
