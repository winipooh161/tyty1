<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'provider',
        'provider_id',
        'avatar',
        'birth_date',
        'gender',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
    ];
    
    /**
     * Получить все шаблоны пользователя.
     */
    public function userTemplates()
    {
        return $this->hasMany(UserTemplate::class);
    }
    
    /**
     * Получение шаблонов, приобретенных пользователем
     */
    public function acquiredTemplates()
    {
        return $this->hasMany(AcquiredTemplate::class);
    }
    
    /**
     * Получение папок шаблонов пользователя
     */
    public function templateFolders()
    {
        return $this->hasMany(TemplateFolder::class);
    }
    
    /**
     * Получение папок для полученных шаблонов пользователя
     */
    public function acquiredTemplateFolders()
    {
        return $this->hasMany(AcquiredTemplateFolder::class);
    }

    /**
     * Получить баланс SUP пользователя
     */
    public function supBalance()
    {
        return $this->hasOne(SupBalance::class);
    }

    /**
     * Получить все транзакции SUP пользователя
     */
    public function supTransactions()
    {
        return $this->hasMany(SupTransaction::class);
    }

    /**
     * Получить или создать баланс SUP для пользователя
     * 
     * @return SupBalance
     */
    public function getOrCreateSupBalance()
    {
        $balance = $this->supBalance;
        
        // Если баланс не существует, создаем его
        if (!$balance) {
            $balance = SupBalance::create([
                'user_id' => $this->id,
                'balance' => 0,
                'total_earned' => 0,
                'total_spent' => 0
            ]);
        }
        
        return $balance;
    }
    
    /**
     * Получить текущий баланс SUP для отображения (числовое значение)
     */
    public function getSupBalanceValueAttribute()
    {
        // Загружаем отношение если не загружено
        if (!$this->relationLoaded('supBalance')) {
            $this->load('supBalance');
        }
        
        $balance = $this->getRelation('supBalance');
        return $balance ? (float)$balance->balance : 0;
    }
    
    /**
     * Проверяет, имеет ли пользователь VIP статус
     *
     * @return bool
     */
    public function isVip()
    {
        return $this->status === 'vip';
    }
}
